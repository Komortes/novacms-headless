<?php

namespace App\Services;

use App\Enums\SummaryStatus;
use App\Models\ContentAiSummary;
use App\Models\ContentAiSummaryEvent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Throwable;

class RuntimeHealthService
{
    /**
     * @return array{
     *     ok: bool,
     *     checks: list<array{component: string, status: string, message: string, meta: array<string, mixed>}>,
     *     alerts: list<array{code: string, severity: string, title: string, message: string, value: string|int|float, threshold: string|int|float}>,
     *     generated_at: string
     * }
     */
    public function collect(): array
    {
        $checks = [
            $this->checkDatabase(),
            $this->checkRedis(),
            $this->checkHorizon(),
            $this->checkReverb(),
            $this->checkOllama(),
        ];

        $ok = collect($checks)->every(fn (array $check): bool => $check['status'] === 'ok');

        return [
            'ok' => $ok,
            'checks' => $checks,
            'alerts' => $this->queueAlerts(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return list<array{code: string, severity: string, title: string, message: string, value: string|int|float, threshold: string|int|float}>
     */
    public function queueAlerts(): array
    {
        try {
            $alerts = [];
            $queueDepthThreshold = max(1, (int) config('ops.alerts.queue_depth_threshold', 25));
            $queueLagMinutesThreshold = max(1, (int) config('ops.alerts.queue_lag_minutes_threshold', 15));
            $failedGrowthThreshold = max(1, (int) config('ops.alerts.failed_growth_per_hour_threshold', 3));
            $failedAbsoluteThreshold = max(1, (int) config('ops.alerts.failed_absolute_per_hour_threshold', 5));

            $pendingCount = ContentAiSummary::query()
                ->where('status', SummaryStatus::PENDING->value)
                ->count();
            $generatingCount = ContentAiSummary::query()
                ->where('status', SummaryStatus::GENERATING->value)
                ->count();
            $queueDepth = $pendingCount + $generatingCount;

            if ($queueDepth >= $queueDepthThreshold) {
                $alerts[] = [
                    'code' => 'queue_depth',
                    'severity' => 'danger',
                    'title' => 'Queue depth is high',
                    'message' => 'Pending + generating runs exceeded configured threshold.',
                    'value' => $queueDepth,
                    'threshold' => $queueDepthThreshold,
                ];
            }

            $oldestPending = ContentAiSummary::query()
                ->where('status', SummaryStatus::PENDING->value)
                ->oldest('updated_at')
                ->first();
            $lagMinutes = $oldestPending?->updated_at?->diffInMinutes(now());

            if (is_numeric($lagMinutes) && (int) round((float) $lagMinutes) >= $queueLagMinutesThreshold) {
                $lagValue = (int) round((float) $lagMinutes);

                $alerts[] = [
                    'code' => 'queue_lag',
                    'severity' => 'danger',
                    'title' => 'Queue lag is high',
                    'message' => 'Oldest pending item has been waiting too long.',
                    'value' => $lagValue,
                    'threshold' => $queueLagMinutesThreshold,
                ];
            }

            $windowStart = now()->subHour();
            $previousWindowStart = now()->subHours(2);
            $failedLastHour = ContentAiSummaryEvent::query()
                ->where('event', 'failed')
                ->where('created_at', '>=', $windowStart)
                ->count();
            $failedPreviousHour = ContentAiSummaryEvent::query()
                ->where('event', 'failed')
                ->whereBetween('created_at', [$previousWindowStart, $windowStart])
                ->count();
            $failedGrowth = $failedLastHour - $failedPreviousHour;

            if ($failedLastHour >= $failedAbsoluteThreshold) {
                $alerts[] = [
                    'code' => 'failed_absolute',
                    'severity' => 'warning',
                    'title' => 'Failed runs rate is elevated',
                    'message' => 'Failed summary runs in the last hour reached alert threshold.',
                    'value' => $failedLastHour,
                    'threshold' => $failedAbsoluteThreshold,
                ];
            }

            if ($failedGrowth >= $failedGrowthThreshold) {
                $alerts[] = [
                    'code' => 'failed_growth',
                    'severity' => 'danger',
                    'title' => 'Failed runs are increasing',
                    'message' => 'Failed summary runs are growing compared with the previous hour.',
                    'value' => $failedGrowth,
                    'threshold' => $failedGrowthThreshold,
                ];
            }

            return $alerts;
        } catch (Throwable $exception) {
            return [[
                'code' => 'queue_metrics_unavailable',
                'severity' => 'warning',
                'title' => 'Queue metrics unavailable',
                'message' => 'Queue alert checks could not be computed: '.trim($exception->getMessage()),
                'value' => 'n/a',
                'threshold' => 'n/a',
            ]];
        }
    }

    /**
     * @return array{component: string, status: string, message: string, meta: array<string, mixed>}
     */
    private function checkDatabase(): array
    {
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            $connection->getPdo();

            if ($driver !== 'pgsql') {
                return $this->warn('Database', 'Connected, but driver is not PostgreSQL.', [
                    'driver' => $driver,
                ]);
            }

            $result = DB::selectOne("SELECT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'vector') AS installed");
            $installed = (bool) data_get((array) $result, 'installed', false);

            if (! $installed) {
                return $this->fail('Database', 'PostgreSQL is reachable but pgvector extension is missing.', [
                    'driver' => $driver,
                ]);
            }

            return $this->ok('Database', 'PostgreSQL and pgvector are reachable.', [
                'driver' => $driver,
            ]);
        } catch (Throwable $exception) {
            return $this->fail('Database', 'Database connection failed: '.$exception->getMessage());
        }
    }

    /**
     * @return array{component: string, status: string, message: string, meta: array<string, mixed>}
     */
    private function checkRedis(): array
    {
        try {
            $pong = Redis::connection()->ping();
            $isPong = $pong === true || strtoupper((string) $pong) === 'PONG';

            if (! $isPong) {
                return $this->fail('Redis', 'Redis ping returned unexpected response.', [
                    'ping' => is_scalar($pong) ? (string) $pong : gettype($pong),
                ]);
            }

            $summaryQueue = (string) config('ai.jobs.summary.queue', 'ai');
            $embeddingQueue = (string) config('ai.jobs.embeddings.queue', 'ai');
            $summaryDepth = (int) Redis::llen('queues:'.$summaryQueue);
            $embeddingDepth = (int) Redis::llen('queues:'.$embeddingQueue);

            return $this->ok('Redis', 'Redis is reachable.', [
                'summary_queue_depth' => $summaryDepth,
                'embeddings_queue_depth' => $embeddingDepth,
            ]);
        } catch (Throwable $exception) {
            return $this->fail('Redis', 'Redis health check failed: '.$exception->getMessage());
        }
    }

    /**
     * @return array{component: string, status: string, message: string, meta: array<string, mixed>}
     */
    private function checkHorizon(): array
    {
        try {
            if ((string) config('queue.default') !== 'redis') {
                return $this->warn('Horizon', 'Queue default is not redis; Horizon is not the primary worker path.', [
                    'queue_default' => (string) config('queue.default'),
                ]);
            }

            Artisan::call('horizon:status');
            $output = trim(Artisan::output());
            $normalized = strtolower($output);

            if (str_contains($normalized, 'active') || str_contains($normalized, 'running')) {
                return $this->ok('Horizon', 'Horizon is active.', [
                    'status' => $output,
                ]);
            }

            return $this->fail('Horizon', 'Horizon is not active.', [
                'status' => $output,
            ]);
        } catch (Throwable $exception) {
            return $this->fail('Horizon', 'Horizon status check failed: '.$exception->getMessage());
        }
    }

    /**
     * @return array{component: string, status: string, message: string, meta: array<string, mixed>}
     */
    private function checkReverb(): array
    {
        $host = (string) (config('reverb.apps.apps.0.options.host') ?: config('reverb.servers.reverb.hostname') ?: '127.0.0.1');
        $port = (int) (config('reverb.apps.apps.0.options.port') ?: config('reverb.servers.reverb.port') ?: 8080);
        $timeout = max(0.5, (float) config('ops.health.socket_timeout_seconds', 2.0));

        $errorCode = 0;
        $errorMessage = '';
        $socket = @fsockopen($host, $port, $errorCode, $errorMessage, $timeout);

        if (! is_resource($socket)) {
            return $this->fail('Reverb', 'Reverb socket is not reachable.', [
                'host' => $host,
                'port' => $port,
                'error' => trim($errorCode.' '.$errorMessage),
            ]);
        }

        fclose($socket);

        return $this->ok('Reverb', 'Reverb socket is reachable.', [
            'host' => $host,
            'port' => $port,
        ]);
    }

    /**
     * @return array{component: string, status: string, message: string, meta: array<string, mixed>}
     */
    private function checkOllama(): array
    {
        $baseUrl = rtrim((string) config('ai.providers.ollama.base_url', 'http://127.0.0.1:11434'), '/');
        $timeout = max(1, (int) config('ops.health.http_timeout_seconds', 5));

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->timeout($timeout)
                ->get('/api/tags');

            if (! $response->ok()) {
                return $this->fail('Ollama', 'Ollama tags endpoint is not healthy.', [
                    'status' => $response->status(),
                    'base_url' => $baseUrl,
                ]);
            }

            $models = collect((array) $response->json('models'))
                ->pluck('name')
                ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->map(fn (string $value): string => trim($value))
                ->values();

            $requiredModels = [];
            if ((string) config('ai.provider') === 'ollama') {
                $requiredModels[] = (string) config('ai.providers.ollama.model');
            }

            if ((string) config('ai.embeddings.provider', 'ollama') === 'ollama') {
                $requiredModels[] = (string) config('ai.embeddings.model');
            }

            $requiredModels = collect($requiredModels)
                ->filter(fn (string $model): bool => trim($model) !== '')
                ->unique()
                ->values();
            $missing = $requiredModels
                ->reject(fn (string $model): bool => $models->contains($model))
                ->values()
                ->all();

            if ($missing !== []) {
                return $this->fail('Ollama', 'Ollama is reachable but required models are missing.', [
                    'base_url' => $baseUrl,
                    'missing_models' => $missing,
                ]);
            }

            return $this->ok('Ollama', 'Ollama is reachable and required models are installed.', [
                'base_url' => $baseUrl,
                'models_count' => $models->count(),
            ]);
        } catch (Throwable $exception) {
            return $this->fail('Ollama', 'Ollama health check failed: '.$exception->getMessage(), [
                'base_url' => $baseUrl,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{component: string, status: string, message: string, meta: array<string, mixed>}
     */
    private function ok(string $component, string $message, array $meta = []): array
    {
        return [
            'component' => $component,
            'status' => 'ok',
            'message' => $message,
            'meta' => $meta,
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{component: string, status: string, message: string, meta: array<string, mixed>}
     */
    private function warn(string $component, string $message, array $meta = []): array
    {
        return [
            'component' => $component,
            'status' => 'warn',
            'message' => $message,
            'meta' => $meta,
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{component: string, status: string, message: string, meta: array<string, mixed>}
     */
    private function fail(string $component, string $message, array $meta = []): array
    {
        return [
            'component' => $component,
            'status' => 'fail',
            'message' => $message,
            'meta' => $meta,
        ];
    }
}

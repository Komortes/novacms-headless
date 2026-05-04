<?php

namespace App\Jobs;

use App\Models\ModelPullTask;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PullOllamaModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(
        public readonly int $taskId,
    ) {}

    public function handle(): void
    {
        $task = ModelPullTask::find($this->taskId);

        if (! $task) {
            return;
        }

        $baseUrl = rtrim((string) config('ai.providers.ollama.base_url', 'http://127.0.0.1:11434'), '/');

        $task->update(['status' => 'pulling', 'status_text' => 'Connecting…']);

        try {
            $client = new Client;
            $response = $client->post($baseUrl.'/api/pull', [
                RequestOptions::JSON => ['model' => $task->model, 'stream' => true],
                RequestOptions::STREAM => true,
                RequestOptions::TIMEOUT => 3600,
                RequestOptions::CONNECT_TIMEOUT => 15,
            ]);

            $body = $response->getBody();
            $buffer = '';
            $lastProgress = 0;

            while (! $body->eof()) {
                $chunk = $body->read(4096);

                if ($chunk === false || $chunk === '') {
                    continue;
                }

                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    if ($line === '') {
                        continue;
                    }

                    $data = json_decode($line, true);

                    if (! is_array($data)) {
                        continue;
                    }

                    $statusText = (string) ($data['status'] ?? '');

                    if ($statusText === 'success') {
                        $task->update([
                            'status' => 'success',
                            'status_text' => 'Model ready',
                            'progress' => 100,
                        ]);

                        return;
                    }

                    $total = (int) ($data['total'] ?? 0);
                    $completed = (int) ($data['completed'] ?? 0);

                    if ($total > 0) {
                        $lastProgress = min(99, (int) round($completed / $total * 100));
                    }

                    $task->update([
                        'status' => 'pulling',
                        'status_text' => $statusText,
                        'progress' => $lastProgress,
                    ]);
                }
            }

            $task->refresh();

            if ($task->status !== 'success') {
                $task->update(['status' => 'success', 'status_text' => 'Model ready', 'progress' => 100]);
            }
        } catch (GuzzleException $e) {
            $task->update([
                'status' => 'failed',
                'status_text' => 'Pull failed',
                'error' => substr($e->getMessage(), 0, 1000),
            ]);
        } catch (Throwable $e) {
            $task->update([
                'status' => 'failed',
                'status_text' => 'Unexpected error',
                'error' => substr($e->getMessage(), 0, 1000),
            ]);
        }
    }
}

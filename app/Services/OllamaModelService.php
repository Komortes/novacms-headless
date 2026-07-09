<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class OllamaModelService
{
    private function baseUrl(): string
    {
        return rtrim((string) config('ai.providers.ollama.base_url', 'http://127.0.0.1:11434'), '/');
    }

    /**
     * @return list<array{name: string, size: int, modified_at: string, parameter_size: string|null, family: string|null}>
     */
    public function listInstalledModels(): array
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl().'/api/tags');

            if (! $response->ok()) {
                return [];
            }

            $models = $response->json('models', []);

            if (! is_array($models)) {
                return [];
            }

            return array_values(array_map(function (mixed $m): array {
                $m = is_array($m) ? $m : [];

                return [
                    'name' => (string) ($m['name'] ?? ''),
                    'size' => (int) ($m['size'] ?? 0),
                    'modified_at' => (string) ($m['modified_at'] ?? ''),
                    'parameter_size' => isset($m['details']['parameter_size']) && is_string($m['details']['parameter_size'])
                        ? $m['details']['parameter_size']
                        : null,
                    'family' => isset($m['details']['family']) && is_string($m['details']['family'])
                        ? $m['details']['family']
                        : null,
                ];
            }, $models));
        } catch (Throwable) {
            return [];
        }
    }

    public function deleteModel(string $model): bool
    {
        try {
            $response = Http::timeout(15)
                ->asJson()
                ->delete($this->baseUrl().'/api/delete', ['model' => $model]);

            return $response->successful();
        } catch (Throwable) {
            return false;
        }
    }

    public function isReachable(): bool
    {
        try {
            return Http::timeout(3)->get($this->baseUrl().'/api/tags')->ok();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Curated list of recommended Ollama models.
     *
     * @return list<array{id: string, name: string, description: string, size_approx: string, param_size: string, category: string, tone: string}>
     */
    public static function catalog(): array
    {
        return [
            // ── Generation: tiny ─────────────────────────────────────────
            [
                'id' => 'qwen2.5:0.5b',
                'name' => 'Qwen 2.5 · 0.5B',
                'description' => 'Smallest usable generation model. Draft checks, token budgets under pressure.',
                'size_approx' => '398 MB',
                'param_size' => '0.5B',
                'category' => 'generation',
                'tone' => 'sky',
            ],
            [
                'id' => 'llama3.2:1b',
                'name' => 'Llama 3.2 · 1B',
                'description' => 'Meta nano Llama. Fast, low memory, acceptable for short content ops.',
                'size_approx' => '1.3 GB',
                'param_size' => '1B',
                'category' => 'generation',
                'tone' => 'sky',
            ],
            [
                'id' => 'gemma3:1b',
                'name' => 'Gemma 3 · 1B',
                'description' => 'Google smallest Gemma. Useful as a draft-quality generation baseline.',
                'size_approx' => '815 MB',
                'param_size' => '1B',
                'category' => 'generation',
                'tone' => 'sky',
            ],
            [
                'id' => 'deepseek-r1:1.5b',
                'name' => 'DeepSeek R1 · 1.5B',
                'description' => 'Distilled reasoning model. Think-step output before the answer.',
                'size_approx' => '1.1 GB',
                'param_size' => '1.5B',
                'category' => 'generation',
                'tone' => 'sky',
            ],
            // ── Generation: small ─────────────────────────────────────────
            [
                'id' => 'qwen2.5:1.5b',
                'name' => 'Qwen 2.5 · 1.5B',
                'description' => 'Balanced lightweight model. Default for most NovaCMS generation tasks.',
                'size_approx' => '986 MB',
                'param_size' => '1.5B',
                'category' => 'generation',
                'tone' => 'indigo',
            ],
            [
                'id' => 'qwen2.5:3b',
                'name' => 'Qwen 2.5 · 3B',
                'description' => 'Step up from 1.5B. Better structure and coherence on longer prompts.',
                'size_approx' => '1.9 GB',
                'param_size' => '3B',
                'category' => 'generation',
                'tone' => 'indigo',
            ],
            [
                'id' => 'llama3.2:3b',
                'name' => 'Llama 3.2 · 3B',
                'description' => 'Meta recommended 3B for instruction following and content drafting.',
                'size_approx' => '2.0 GB',
                'param_size' => '3B',
                'category' => 'generation',
                'tone' => 'indigo',
            ],
            [
                'id' => 'gemma3:4b',
                'name' => 'Gemma 3 · 4B',
                'description' => 'Google mid-size Gemma. Solid output quality for editorial generation.',
                'size_approx' => '3.3 GB',
                'param_size' => '4B',
                'category' => 'generation',
                'tone' => 'indigo',
            ],
            [
                'id' => 'phi4-mini:3.8b',
                'name' => 'Phi 4 Mini · 3.8B',
                'description' => 'Microsoft compact reasoning model. Good reasoning-to-size ratio.',
                'size_approx' => '2.5 GB',
                'param_size' => '3.8B',
                'category' => 'generation',
                'tone' => 'indigo',
            ],
            // ── Generation: medium ────────────────────────────────────────
            [
                'id' => 'qwen2.5:7b',
                'name' => 'Qwen 2.5 · 7B',
                'description' => 'Higher quality generation, needs at least 8 GB VRAM or RAM.',
                'size_approx' => '4.7 GB',
                'param_size' => '7B',
                'category' => 'generation',
                'tone' => 'amber',
            ],
            [
                'id' => 'mistral:7b',
                'name' => 'Mistral · 7B',
                'description' => 'Classic strong 7B model. Reliable for structured output and summaries.',
                'size_approx' => '4.1 GB',
                'param_size' => '7B',
                'category' => 'generation',
                'tone' => 'amber',
            ],
            [
                'id' => 'llama3.1:8b',
                'name' => 'Llama 3.1 · 8B',
                'description' => 'Meta flagship 8B. Excellent instruction following, high quality output.',
                'size_approx' => '4.7 GB',
                'param_size' => '8B',
                'category' => 'generation',
                'tone' => 'amber',
            ],
            [
                'id' => 'gemma3:12b',
                'name' => 'Gemma 3 · 12B',
                'description' => 'Google larger Gemma. Noticeably better coherence on complex tasks.',
                'size_approx' => '8.1 GB',
                'param_size' => '12B',
                'category' => 'generation',
                'tone' => 'amber',
            ],
            [
                'id' => 'deepseek-r1:7b',
                'name' => 'DeepSeek R1 · 7B',
                'description' => 'Mid-size reasoning model. Visible thinking chain before the answer.',
                'size_approx' => '4.7 GB',
                'param_size' => '7B',
                'category' => 'generation',
                'tone' => 'amber',
            ],
            // ── Embeddings ─────────────────────────────────────────────────
            [
                'id' => 'nomic-embed-text',
                'name' => 'Nomic Embed Text',
                'description' => 'Default NovaCMS embedding model. 768-dim, fast, practical for semantic search.',
                'size_approx' => '274 MB',
                'param_size' => '137M',
                'category' => 'embeddings',
                'tone' => 'emerald',
            ],
            [
                'id' => 'mxbai-embed-large',
                'name' => 'MxBai Embed Large',
                'description' => 'Higher-quality embeddings at the cost of size. Better recall on dense corpora.',
                'size_approx' => '670 MB',
                'param_size' => '335M',
                'category' => 'embeddings',
                'tone' => 'emerald',
            ],
            [
                'id' => 'all-minilm',
                'name' => 'All-MiniLM',
                'description' => 'Tiny sentence embedding model. Excellent speed, lower recall than nomic.',
                'size_approx' => '46 MB',
                'param_size' => '22M',
                'category' => 'embeddings',
                'tone' => 'emerald',
            ],
        ];
    }
}

<?php

namespace App\AI\Contracts;

use App\AI\Data\AiGenerationResult;

interface AiProviderInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function generate(string $prompt, array $options = []): AiGenerationResult;

    /**
     * @param  array<string, mixed>  $options
     * @return list<float>
     */
    public function embed(string $input, array $options = []): array;
}

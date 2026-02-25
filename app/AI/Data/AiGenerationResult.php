<?php

namespace App\AI\Data;

final readonly class AiGenerationResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $text,
        public string $model,
        public ?int $tokensIn = null,
        public ?int $tokensOut = null,
        public array $raw = [],
    ) {
    }
}


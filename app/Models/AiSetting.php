<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'default_provider',
        'ollama_base_url',
        'ollama_model',
        'ollama_timeout',
        'openai_base_url',
        'openai_api_key',
        'openai_model',
        'openai_timeout',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ollama_timeout' => 'integer',
            'openai_timeout' => 'integer',
            'openai_api_key' => 'encrypted',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentEmbedding extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'content_id',
        'source',
        'chunk_index',
        'content_hash',
        'provider',
        'model',
        'dimensions',
        'embedding',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'dimensions' => 'integer',
            'embedding' => 'array',
            'meta' => 'array',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}

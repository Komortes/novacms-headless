<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentAiSummaryEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'content_id',
        'content_ai_summary_id',
        'event',
        'provider',
        'model',
        'queue_version',
        'wait_ms',
        'duration_ms',
        'message',
        'meta',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'wait_ms' => 'integer',
            'duration_ms' => 'integer',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function summary(): BelongsTo
    {
        return $this->belongsTo(ContentAiSummary::class, 'content_ai_summary_id');
    }
}

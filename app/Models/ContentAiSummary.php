<?php

namespace App\Models;

use App\Enums\SummaryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentAiSummary extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'content_id',
        'summary_tldr',
        'summary_bullets',
        'summary_meta_description',
        'summary_faq',
        'summary_tags',
        'status',
        'model',
        'prompt_version',
        'tokens_in',
        'tokens_out',
        'generation_ms',
        'last_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'summary_bullets' => 'array',
            'summary_faq' => 'array',
            'summary_tags' => 'array',
            'status' => SummaryStatus::class,
            'generation_ms' => 'integer',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ContentAiSummaryEvent::class)->orderByDesc('created_at');
    }
}

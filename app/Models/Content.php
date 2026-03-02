<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Content extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'slug',
        'title',
        'body',
        'locale',
        'status',
        'content_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContentType::class,
            'status' => ContentStatus::class,
        ];
    }

    public function summary(): HasOne
    {
        return $this->hasOne(ContentAiSummary::class);
    }

    public function summaryEvents(): HasMany
    {
        return $this->hasMany(ContentAiSummaryEvent::class)->orderByDesc('created_at');
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(ContentEmbedding::class)->orderBy('chunk_index');
    }

    public function latestSummaryEvent(): HasOne
    {
        return $this->hasOne(ContentAiSummaryEvent::class)->latestOfMany('created_at');
    }

    public function generateContentHash(): string
    {
        $payload = [
            'type' => $this->type instanceof ContentType ? $this->type->value : $this->type,
            'slug' => (string) $this->slug,
            'title' => (string) $this->title,
            'body' => (string) $this->body,
            'locale' => (string) $this->locale,
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }
}

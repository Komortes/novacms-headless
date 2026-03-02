<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $dimensions = max(1, (int) config('ai.embeddings.dimensions', 1024));

        if ($driver === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        Schema::create('content_embeddings', function (Blueprint $table) use ($driver) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('source', 40)->default('body');
            $table->unsignedInteger('chunk_index')->default(0);
            $table->char('content_hash', 64);
            $table->string('provider', 40)->default('ollama');
            $table->string('model');
            $table->unsignedInteger('dimensions');
            $table->json('meta')->nullable();
            $table->timestamps();

            if ($driver !== 'pgsql') {
                $table->json('embedding')->nullable();
            }

            $table->unique(['content_id', 'source', 'chunk_index', 'model'], 'content_embeddings_unique_slot');
            $table->index(['content_id', 'source'], 'content_embeddings_content_source_idx');
            $table->index('content_hash', 'content_embeddings_hash_idx');
        });

        if ($driver === 'pgsql') {
            DB::statement(sprintf('ALTER TABLE content_embeddings ADD COLUMN embedding vector(%d)', $dimensions));
            DB::statement('CREATE INDEX content_embeddings_embedding_ivfflat_idx ON content_embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_embeddings');
    }
};

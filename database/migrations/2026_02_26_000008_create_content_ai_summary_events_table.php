<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('content_ai_summary_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('content_ai_summary_id')->nullable()->constrained('content_ai_summaries')->nullOnDelete();
            $table->string('event', 40);
            $table->string('provider', 40)->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('queue_version')->nullable();
            $table->unsignedInteger('wait_ms')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('message', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['content_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_ai_summary_events');
    }
};

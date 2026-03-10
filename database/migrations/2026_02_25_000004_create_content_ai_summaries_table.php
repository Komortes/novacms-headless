<?php

use App\Enums\SummaryStatus;
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
        Schema::create('content_ai_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->text('summary_tldr')->nullable();
            $table->json('summary_bullets')->nullable();
            $table->text('summary_meta_description')->nullable();
            $table->json('summary_faq')->nullable();
            $table->json('summary_tags')->nullable();
            $table->string('status', 20)->default(SummaryStatus::PENDING->value);
            $table->string('model')->nullable();
            $table->string('prompt_version')->nullable();
            $table->unsignedInteger('tokens_in')->nullable();
            $table->unsignedInteger('tokens_out')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('content_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_ai_summaries');
    }
};

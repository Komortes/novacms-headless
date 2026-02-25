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
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('default_provider', 32)->default('ollama');

            $table->string('ollama_base_url')->nullable();
            $table->string('ollama_model')->nullable();
            $table->unsignedSmallInteger('ollama_timeout')->nullable();

            $table->string('openai_base_url')->nullable();
            $table->text('openai_api_key')->nullable();
            $table->string('openai_model')->nullable();
            $table->unsignedSmallInteger('openai_timeout')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};

<?php

use App\Enums\ContentStatus;
use App\Enums\ContentType;
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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->default(ContentType::POST->value);
            $table->string('slug');
            $table->string('title');
            $table->longText('body');
            $table->string('locale', 10)->default('en');
            $table->string('status', 20)->default(ContentStatus::DRAFT->value);
            $table->char('content_hash', 64);
            $table->timestamps();

            $table->unique(['slug', 'locale']);
            $table->index('status');
            $table->index('content_hash');
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};


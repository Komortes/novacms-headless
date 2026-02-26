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
        Schema::table('content_ai_summaries', function (Blueprint $table) {
            $table->unsignedInteger('generation_ms')->nullable()->after('tokens_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_ai_summaries', function (Blueprint $table) {
            $table->dropColumn('generation_ms');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->json('ai_analysis')->nullable()->after('ai_analysis_status');
        });

        // The "AI history" feature (a browsable log of every generation) is
        // explicitly out of scope for the simplified AI module — the Lead's
        // own ai_analysis column is now the single source of truth, and
        // "latest report always available" replaces the need for versioned
        // history entirely.
        Schema::dropIfExists('ai_generations');
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('ai_analysis');
        });

        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->nullableMorphs('generatable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('input')->nullable();
            $table->longText('output')->nullable();
            $table->string('model')->nullable();
            $table->timestamps();
        });
    }
};

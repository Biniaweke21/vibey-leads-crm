<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stored AI Workspace output (analysis / proposal / email / tasks).
        // Polymorphic source so it can hang off a lead, client, or project.
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();  // analysis | proposal | email | tasks
            $table->nullableMorphs('generatable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('input')->nullable();
            $table->longText('output')->nullable();
            $table->string('model')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};

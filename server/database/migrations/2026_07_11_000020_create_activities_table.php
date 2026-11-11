<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic timeline / audit log for any entity.
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');    // subject_type + subject_id (+ index)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 500);
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};

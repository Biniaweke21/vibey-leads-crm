<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic — reused across leads, clients, projects, meetings, documents.
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('noteable');   // noteable_type + noteable_id (+ index)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};

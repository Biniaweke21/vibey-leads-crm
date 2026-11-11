<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DB-level backstop for "prevent duplicate attendees" (in addition to
        // the Service-level check) — MySQL treats each NULL as distinct, so
        // legacy free-text (no user_id) attendees are unaffected.
        Schema::table('meeting_attendees', function (Blueprint $table) {
            $table->unique(['meeting_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('meeting_attendees', function (Blueprint $table) {
            $table->dropUnique(['meeting_id', 'user_id']);
        });
    }
};

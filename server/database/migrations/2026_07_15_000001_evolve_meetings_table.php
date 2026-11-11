<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw renames (MySQL preserves the existing FK constraint on organizer_id/created_by
        // across a CHANGE rename — no doctrine/dbal dependency needed).
        DB::statement('ALTER TABLE meetings CHANGE scheduled_at starts_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE meetings CHANGE created_by organizer_id BIGINT UNSIGNED NULL');

        Schema::table('meetings', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->string('type')->default('other')->after('description');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
            $table->string('timezone')->default('Africa/Addis_Ababa')->after('ends_at');
            $table->string('meeting_url')->nullable()->after('location');
            $table->text('internal_notes')->nullable()->after('meeting_url');
            // Becomes a computed attribute (starts_at -> ends_at diff) — see
            // Meeting model — so it can never drift from the actual times,
            // mirroring the line-item/document totals pattern from Phase 6.
            $table->dropColumn('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['description', 'type', 'ends_at', 'timezone', 'meeting_url', 'internal_notes']);
            $table->unsignedSmallInteger('duration_minutes')->default(30);
        });

        DB::statement('ALTER TABLE meetings CHANGE starts_at scheduled_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE meetings CHANGE organizer_id created_by BIGINT UNSIGNED NULL');
    }
};

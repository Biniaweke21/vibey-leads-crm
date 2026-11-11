<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_submissions', function (Blueprint $table) {
            // "Title" is the existing `name` column; "Attachment reference" is
            // the existing `url` column — no upload endpoint exists yet to
            // populate a separate FK to the polymorphic attachments table, so
            // adding one would be an unused, unpopulatable duplicate for now.
            $table->text('description')->nullable()->after('name');
            $table->unsignedSmallInteger('version')->default(1)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('design_submissions', function (Blueprint $table) {
            $table->dropColumn(['description', 'version']);
        });
    }
};

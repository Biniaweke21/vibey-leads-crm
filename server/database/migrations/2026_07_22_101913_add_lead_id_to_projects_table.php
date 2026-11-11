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
        Schema::table('projects', function (Blueprint $table) {
            // Traces a Project back to the Lead it was converted from, so its
            // AI Requirement Analysis stays reachable after conversion —
            // nullable since manually created projects have no source lead.
            $table->foreignId('lead_id')->nullable()->after('client_id')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lead_id');
        });
    }
};

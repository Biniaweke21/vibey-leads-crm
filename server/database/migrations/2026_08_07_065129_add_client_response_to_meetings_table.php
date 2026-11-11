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
        Schema::table('meetings', function (Blueprint $table) {
            // Null = client hasn't responded yet.
            $table->string('client_response')->nullable()->after('meeting_url');
            $table->text('client_response_note')->nullable()->after('client_response');
            $table->timestamp('client_responded_at')->nullable()->after('client_response_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['client_response', 'client_response_note', 'client_responded_at']);
        });
    }
};

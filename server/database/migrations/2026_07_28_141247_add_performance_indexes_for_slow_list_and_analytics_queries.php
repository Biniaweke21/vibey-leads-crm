<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `created_at` is the default sort column (and a range-filterable one) on
 * every one of these list endpoints, but Laravel's timestamps() never
 * indexes it — every unfiltered/default-sorted list request was doing a
 * full table scan + filesort. `document_payments.paid_at` is whereBetween'd
 * or summed by 5 separate AnalyticsService queries; `documents.due_date` is
 * a user-selectable sort column on the client portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', fn (Blueprint $table) => $table->index('created_at'));
        Schema::table('clients', fn (Blueprint $table) => $table->index('created_at'));
        Schema::table('projects', fn (Blueprint $table) => $table->index('created_at'));
        Schema::table('documents', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('due_date');
        });
        Schema::table('meetings', fn (Blueprint $table) => $table->index('created_at'));
        Schema::table('document_payments', fn (Blueprint $table) => $table->index('paid_at'));
    }

    public function down(): void
    {
        Schema::table('leads', fn (Blueprint $table) => $table->dropIndex(['created_at']));
        Schema::table('clients', fn (Blueprint $table) => $table->dropIndex(['created_at']));
        Schema::table('projects', fn (Blueprint $table) => $table->dropIndex(['created_at']));
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['due_date']);
        });
        Schema::table('meetings', fn (Blueprint $table) => $table->dropIndex(['created_at']));
        Schema::table('document_payments', fn (Blueprint $table) => $table->dropIndex(['paid_at']));
    }
};

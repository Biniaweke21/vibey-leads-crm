<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Which installment a payment represents (deposit/mid-project/final) — set on client-submitted payment proofs, optional on team-recorded ones. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_payments', function (Blueprint $table) {
            $table->string('stage')->nullable()->after('method');
        });
    }

    public function down(): void
    {
        Schema::table('document_payments', function (Blueprint $table) {
            $table->dropColumn('stage');
        });
    }
};

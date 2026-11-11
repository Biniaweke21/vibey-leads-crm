<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_payments', function (Blueprint $table) {
            $table->string('status')->default('paid')->after('amount')->index();
            $table->string('reference_number')->nullable()->after('method');
            $table->text('notes')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('document_payments', function (Blueprint $table) {
            $table->dropColumn(['status', 'reference_number', 'notes']);
        });
    }
};

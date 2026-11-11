<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `payments` table (Client-level, no link to a Document) was dead
     * code — nothing in the app ever wrote to it. Every real payment flows
     * through `document_payments` (tied to an invoice/document), which is
     * what Analytics/Client revenue now read from instead.
     */
    public function up(): void
    {
        Schema::dropIfExists('payments');
    }

    public function down(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('paid')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
};

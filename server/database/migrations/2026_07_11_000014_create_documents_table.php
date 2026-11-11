<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single table for proposals, contracts, and invoices (type column).
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();                 // proposal | contract | invoice
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('number')->nullable()->unique();  // invoice/contract number
            $table->string('status')->default('draft')->index();
            $table->unsignedSmallInteger('version')->default(1);
            $table->char('currency', 3)->default('ETB');
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('signature_status')->nullable();  // contracts only
            // type-specific narrative: executive_summary, scope, deliverables[],
            // timeline_phases[], terms, contract start/end, etc.
            $table->json('body')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

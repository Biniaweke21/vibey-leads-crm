<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_client_id')->nullable()->constrained('clients')->nullOnDelete();

            // Contact
            $table->string('full_name');
            $table->string('company_name')->nullable();
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('city')->nullable();
            $table->string('preferred_contact')->nullable();

            // Business
            $table->string('business_name');
            $table->string('industry')->nullable();
            $table->string('website_url')->nullable();
            $table->string('social_links')->nullable();
            $table->text('business_description')->nullable();

            // Project scope
            $table->text('project_idea')->nullable();
            $table->text('main_goal')->nullable();
            $table->text('target_users')->nullable();
            $table->text('problems')->nullable();
            $table->text('core_features')->nullable();

            // Multi-value intake (JSON — never queried individually)
            $table->json('services')->nullable();
            $table->string('other_service')->nullable();
            $table->json('features')->nullable();
            $table->string('other_feature')->nullable();
            $table->string('design_style')->nullable();
            $table->json('reference_links')->nullable();

            $table->string('budget')->nullable();
            $table->string('timeline')->nullable();
            $table->text('additional_notes')->nullable();

            // Pipeline
            $table->string('status')->default('new')->index();
            $table->string('priority')->default('cold')->index();
            $table->string('ai_analysis_status')->default('pending');
            $table->timestamp('stage_entered_at')->nullable();
            $table->date('due_date')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

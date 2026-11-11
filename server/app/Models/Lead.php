<?php

namespace App\Models;

use App\Enums\AiAnalysisStatus;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Concerns\HasCrmRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasCrmRelations, HasFactory, SoftDeletes;

    /** Mirrors the public request-form catalogs (client/src/features/landing/data/requestForm.ts). */
    public const CONTACT_METHODS = ['WhatsApp', 'Call', 'Email'];

    public const SERVICE_OPTIONS = [
        'Website', 'Mobile App', 'ERP System', 'AI Solution', 'Branding',
        'UI/UX Design', 'E-commerce', 'SEO', 'Social Media Management', 'Custom Software',
    ];

    public const DESIGN_STYLES = ['Minimal', 'Luxury', 'Corporate', 'Modern', 'Dark', 'Colorful', 'Premium'];

    public const BUDGET_RANGES = [
        'Under 30,000 ETB', '30,000 – 50,000 ETB', '50,000 – 100,000 ETB',
        '100,000 – 300,000 ETB', '300,000 ETB+', 'To be discussed',
    ];

    public const TIMELINE_OPTIONS = ['ASAP (within 2 weeks)', '1 Month', '2 Months', '3 Months', 'Flexible / No Rush'];

    protected $fillable = [
        'assigned_to', 'converted_client_id',
        'full_name', 'company_name', 'email', 'phone', 'whatsapp', 'city', 'preferred_contact',
        'business_name', 'industry', 'website_url', 'social_links', 'business_description',
        'project_idea', 'main_goal', 'target_users', 'problems', 'core_features',
        'services', 'other_service', 'features', 'other_feature', 'design_style', 'reference_links',
        'budget', 'timeline', 'additional_notes',
        'status', 'priority', 'ai_analysis_status', 'ai_analysis', 'stage_entered_at', 'due_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'priority' => LeadPriority::class,
            'ai_analysis_status' => AiAnalysisStatus::class,
            'ai_analysis' => 'array',
            'services' => 'array',
            'features' => 'array',
            'reference_links' => 'array',
            'stage_entered_at' => 'datetime',
            'due_date' => 'date',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'converted_client_id');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    /** The Project this lead was converted into, if conversion has happened. */
    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }
}

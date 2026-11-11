<?php

namespace App\Services\Ai;

use App\Contracts\AiEngineInterface;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Lead;

/**
 * Deterministic, no-external-API requirement analyzer. Every section is
 * derived from the actual submitted Lead fields (services, selected
 * features, budget, timeline, free-text descriptions) so two different
 * leads produce genuinely different output — nothing here is printed
 * unconditionally. Swap this class for a real LLM-backed engine later by
 * binding a different `AiEngineInterface` implementation; nothing outside
 * this file needs to change.
 */
class RuleBasedAiEngine implements AiEngineInterface
{
    /**
     * Rough weeks-of-work per service — drives the timeline estimate only.
     * ponytail: no price column here on purpose. This offline engine has no
     * way to check real-world market pricing, so buildQuotation() quotes
     * directly from the client's own stated budget instead of a stale
     * internal price list. GeminiAiEngine (when configured) does the real
     * market-pricing research and skips this fallback entirely.
     */
    private const SERVICE_WEEKS = [
        'Website' => 3,
        'Mobile App' => 6,
        'ERP System' => 10,
        'AI Solution' => 7,
        'Branding' => 2,
        'UI/UX Design' => 2,
        'E-commerce' => 4,
        'SEO' => 2,
        'Social Media Management' => 1,
        'Custom Software' => 9,
    ];

    private const DEFAULT_WEEKS = 3;

    /** Numeric [low, high] ETB bounds for each Lead::BUDGET_RANGES option — the quotation must never undercut what the client already told us they can spend. */
    private const BUDGET_RANGE_BOUNDS = [
        'Under 30,000 ETB' => [0, 30000],
        '30,000 – 50,000 ETB' => [30000, 50000],
        '50,000 – 100,000 ETB' => [50000, 100000],
        '100,000 – 300,000 ETB' => [100000, 300000],
        '300,000 ETB+' => [300000, 500000],
    ];

    /** Per-service default tech recommendation — combined across every requested service. */
    private const TECH_PROFILES = [
        'Website' => ['frontend' => 'Vue 3 + Tailwind CSS', 'backend' => 'Laravel (PHP)', 'database' => 'MySQL'],
        'Mobile App' => ['frontend' => 'Flutter (single codebase for iOS + Android)', 'backend' => 'Laravel (PHP) REST API', 'database' => 'MySQL'],
        'ERP System' => ['frontend' => 'Vue 3 admin dashboard', 'backend' => 'Laravel (PHP)', 'database' => 'PostgreSQL'],
        'AI Solution' => ['frontend' => 'Vue 3', 'backend' => 'Python (FastAPI) for the AI service + Laravel for the core app', 'database' => 'PostgreSQL'],
        'E-commerce' => ['frontend' => 'Vue 3 storefront', 'backend' => 'Laravel (PHP)', 'database' => 'MySQL'],
        'Custom Software' => ['frontend' => 'Vue 3', 'backend' => 'Laravel (PHP)', 'database' => 'PostgreSQL'],
    ];

    private const AUTH_FEATURES = ['User Login/Auth', 'Auth', 'Admin Panel', 'Social Login'];

    private const PAYMENT_FEATURES = ['Payment Integration', 'Payment'];

    private const REPORTING_FEATURES = ['Reports', 'Analytics', 'Data Analytics'];

    /** [detected missing-requirement label => discovery question] — question set always mirrors the missing-requirement set. */
    private const REQUIREMENT_QUESTIONS = [
        'A confirmed budget range' => 'What is your confirmed budget range for this project?',
        'A firm project deadline' => 'Is there a hard deadline or launch date driving this timeline?',
        'Target user personas' => 'Who are the primary users of this system, and what are their key needs?',
        'User roles & permission levels' => 'What different user roles or permission levels does the system need (e.g. Admin, Staff, Customer)?',
        'Payment gateway & currency preferences' => 'Which payment methods and currencies need to be supported?',
        'Hosting environment & domain ownership' => 'Do you already have hosting and a domain, or should we provision these for you?',
        'Language / localization requirements' => 'Does the platform need to support more than one language?',
        'Reporting & analytics needs' => 'What kind of reports or analytics dashboards do you need to track performance?',
        'Brand guidelines or visual references' => 'Do you have existing brand guidelines, a logo, or reference designs we should follow?',
        'Existing digital presence to integrate with' => 'Do you have an existing website or social presence we should migrate from or integrate with?',
        'Detailed functional requirements' => 'Can you walk us through the core problem this system needs to solve, step by step?',
        'Systems this needs to integrate with' => 'Are there existing tools or systems (CRM, accounting, etc.) this needs to integrate with?',
        'Admin permission structure' => 'What specific admin roles do you need (e.g. Super Admin vs. Editor vs. Viewer)?',
    ];

    public function modelName(): string
    {
        return 'rule-engine-v1';
    }

    public function analyzeLead(Lead $lead): array
    {
        $services = $this->servicesOf($lead);
        $features = $this->flattenFeatures($lead);
        $missing = $this->detectMissingRequirements($lead, $services, $features);

        return [
            'project_summary' => $this->buildProjectSummary($lead, $services),
            'tech_stack' => $this->buildTechStack($lead, $services, $features),
            'complexity' => $this->assessComplexity($lead, $services, $features),
            'timeline' => $this->estimateTimeline($services),
            'missing_requirements' => $missing,
            'questions' => $this->buildQuestions($missing),
            'risks' => $this->identifyRisks($lead, $services, $features),
            'quotation' => $this->buildQuotation($lead, $services),
        ];
    }

    // ---------- 1. Project Summary ----------

    private function buildProjectSummary(Lead $lead, array $services): string
    {
        $business = sprintf(
            '%s is a %s business%s. %s',
            $lead->business_name,
            $lead->industry ? strtolower($lead->industry) : 'growing',
            $lead->city ? " based in {$lead->city}" : '',
            $this->sentence($lead->business_description) ?: 'No detailed business background was provided in the intake.',
        );

        $goal = $lead->main_goal ?: $lead->project_idea ?: 'grow their business with a digital solution';
        $system = sprintf(
            'They are requesting %s to %s.%s%s',
            implode(', ', $services),
            rtrim($goal, '.'),
            $lead->target_users ? " The solution is aimed at {$lead->target_users}." : '',
            $lead->problems ? ' Specifically, it needs to address: '.$this->sentence($lead->problems) : '',
        );

        $features = $this->flattenFeatures($lead);
        $solution = sprintf(
            'Vibey Worlds will design, build, and deliver %s%s. Based on the stated %s budget and %s timeline, this is scoped as a %s-complexity engagement.',
            implode(', ', $services),
            $features ? ' with a feature set including '.implode(', ', array_slice($features, 0, 6)) : '',
            $lead->budget ?: 'unspecified',
            $lead->timeline ? strtolower($lead->timeline) : 'flexible',
            $this->assessComplexity($lead, $services, $features)['level'],
        );

        return trim("{$business}\n\n{$system}\n\n{$solution}");
    }

    // ---------- 2. Suggested Tech Stack ----------

    private function buildTechStack(Lead $lead, array $services, array $features): array
    {
        $profiles = array_values(array_intersect_key(self::TECH_PROFILES, array_flip($services)));
        $frontends = array_unique(array_column($profiles, 'frontend')) ?: ['Vue 3 + Tailwind CSS'];
        $backends = array_unique(array_column($profiles, 'backend')) ?: ['Laravel (PHP)'];
        $databases = array_unique(array_column($profiles, 'database')) ?: ['MySQL'];

        $hasAuth = $this->hasAnyFeature($features, self::AUTH_FEATURES);
        $hasPayment = $this->hasAnyFeature($features, self::PAYMENT_FEATURES);
        $complexity = $this->assessComplexity($lead, $services, $features)['level'];

        $integrations = [];
        if ($hasPayment) {
            $integrations[] = 'a local payment gateway (e.g. Chapa or Telebirr) plus card processing for international clients';
        }
        if ($this->hasAnyFeature($features, ['Maps'])) {
            $integrations[] = 'Google Maps API for location features';
        }
        if ($this->hasAnyFeature($features, ['Push Notifications'])) {
            $integrations[] = 'Firebase Cloud Messaging for push notifications';
        }
        if ($this->hasAnyFeature($features, ['Social Login'])) {
            $integrations[] = 'Google/Facebook OAuth for social login';
        }
        if (in_array('AI Solution', $services, true)) {
            $integrations[] = 'an LLM provider (e.g. OpenAI or Claude) for the AI-driven features';
        }

        return [
            'frontend' => [
                'choice' => implode(' / ', $frontends),
                'why' => 'Matches the requested platform(s) ('.implode(', ', $services).') and lets the team ship a fast, maintainable UI with the component patterns already used across Vibey Worlds projects.',
            ],
            'backend' => [
                'choice' => implode(' / ', $backends),
                'why' => 'A mature, well-supported framework that covers the business logic, API, and admin needs of this scope without unnecessary infrastructure overhead.',
            ],
            'database' => [
                'choice' => implode(' / ', $databases),
                'why' => in_array('PostgreSQL', $databases, true)
                    ? 'The data model for '.implode(' and ', $services).' benefits from PostgreSQL\'s stronger support for complex relational queries and reporting at this scale.'
                    : 'A reliable relational database that fits the data shape of this project without added operational complexity.',
            ],
            'hosting' => [
                'choice' => $complexity === 'Enterprise' || $complexity === 'High'
                    ? 'Cloud VPS with auto-scaling (AWS/GCP)'
                    : 'Managed VPS (e.g. DigitalOcean)',
                'why' => $complexity === 'Enterprise' || $complexity === 'High'
                    ? 'The scope and expected complexity justify infrastructure that can scale with traffic and add redundancy as usage grows.'
                    : 'Cost-effective and sufficient for the expected traffic at this scope, with a clear upgrade path if usage grows.',
            ],
            'authentication' => [
                'choice' => $hasAuth ? 'Email/password with Laravel Sanctum, plus role-based access control' : 'Simple admin-only authentication (Laravel Sanctum)',
                'why' => $hasAuth
                    ? 'User login/admin features were explicitly requested, so token-based auth with per-role permissions is needed from day one.'
                    : 'No end-user login was requested in the intake — authentication can stay scoped to internal/admin access unless discovery surfaces a need for client-facing accounts.',
            ],
            'integrations' => [
                'choice' => $integrations ? implode('; ', $integrations) : 'None identified from the intake yet',
                'why' => $integrations
                    ? 'Selected directly from the features requested in the intake form.'
                    : 'No specific third-party integration was mentioned — to be confirmed during discovery before development starts.',
            ],
        ];
    }

    // ---------- 3. Estimated Complexity ----------

    private function assessComplexity(Lead $lead, array $services, array $features): array
    {
        $score = max(0, count($services) - 1);
        $heavyServices = array_intersect(['ERP System', 'Custom Software', 'AI Solution'], $services);
        $score += count($heavyServices) * 2;
        $score += count($features) > 8 ? 1 : 0;
        $score += $lead->budget === '300,000 ETB+' ? 1 : 0;
        $score += $lead->timeline === 'ASAP (within 2 weeks)' ? 1 : 0;

        [$level, $explanation] = match (true) {
            $score >= 6 => ['Enterprise', 'Multiple substantial services'.($heavyServices ? ' (including '.implode(', ', $heavyServices).')' : '').' combined with a broad feature set point to a multi-team, multi-phase engagement.'],
            $score >= 4 => ['High', 'The combination of '.implode(', ', $services).($heavyServices ? ', including complex systems like '.implode(', ', $heavyServices) : '').', involves significant business logic and integration work.'],
            $score >= 2 => ['Medium', count($services).' requested service(s) with '.count($features).' selected feature(s) represent a moderate, well-scoped build.'],
            default => ['Low', 'A single, well-defined service with a focused feature set — straightforward to scope and deliver.'],
        };

        return ['level' => $level, 'explanation' => $explanation];
    }

    // ---------- 4. Estimated Timeline ----------

    private function estimateTimeline(array $services): array
    {
        $totalWeeks = array_sum(array_map(
            fn ($s) => self::SERVICE_WEEKS[$s] ?? self::DEFAULT_WEEKS,
            $services,
        ));

        $weights = [
            'Discovery' => 0.10,
            'UI/UX' => 0.15,
            'Development' => 0.50,
            'Testing' => 0.15,
            'Deployment' => 0.05,
            'Training' => 0.05,
        ];

        $phaseWeeks = [];
        foreach ($weights as $phase => $weight) {
            $phaseWeeks[$phase] = max(1, (int) round($totalWeeks * $weight));
        }

        $actualTotal = array_sum($phaseWeeks);
        $months = max(1, round($actualTotal / 4));

        return [
            'total_duration' => $actualTotal === 1 ? '1 week' : "{$actualTotal} weeks (~{$months} month".($months == 1 ? '' : 's').')',
            'phases' => array_map(
                fn ($phase, $weeks) => ['phase' => $phase, 'duration' => $weeks === 1 ? '1 week' : "{$weeks} weeks"],
                array_keys($phaseWeeks),
                array_values($phaseWeeks),
            ),
        ];
    }

    // ---------- 5. Missing Requirements ----------

    private function detectMissingRequirements(Lead $lead, array $services, array $features): array
    {
        $missing = [];

        if (! $lead->budget || $lead->budget === 'To be discussed') {
            $missing[] = 'A confirmed budget range';
        }
        if (! $lead->timeline || $lead->timeline === 'Flexible / No Rush') {
            $missing[] = 'A firm project deadline';
        }
        if (! $lead->target_users) {
            $missing[] = 'Target user personas';
        }
        if (! $this->hasAnyFeature($features, self::AUTH_FEATURES) && array_intersect(['Website', 'Mobile App', 'ERP System', 'Custom Software'], $services)) {
            $missing[] = 'User roles & permission levels';
        }
        if (! $this->hasAnyFeature($features, self::PAYMENT_FEATURES) && array_intersect(['E-commerce', 'Website', 'Mobile App'], $services)) {
            $missing[] = 'Payment gateway & currency preferences';
        }
        if (! str_contains(strtolower($lead->business_description.' '.$lead->additional_notes), 'hosting') && ! str_contains(strtolower($lead->business_description.' '.$lead->additional_notes), 'domain')) {
            $missing[] = 'Hosting environment & domain ownership';
        }
        if (! $this->hasAnyFeature($features, ['Multi-language']) && in_array('Website', $services, true)) {
            $missing[] = 'Language / localization requirements';
        }
        if (! $this->hasAnyFeature($features, self::REPORTING_FEATURES) && array_intersect(['ERP System', 'Custom Software', 'Website'], $services)) {
            $missing[] = 'Reporting & analytics needs';
        }
        if (! $lead->design_style && empty($lead->reference_links)) {
            $missing[] = 'Brand guidelines or visual references';
        }
        if (! $lead->website_url && ! $lead->social_links) {
            $missing[] = 'Existing digital presence to integrate with';
        }
        if (! $lead->core_features && ! $lead->problems) {
            $missing[] = 'Detailed functional requirements';
        }
        if (array_intersect(['ERP System', 'Custom Software'], $services) && ! str_contains(strtolower($lead->business_description), 'integrat')) {
            $missing[] = 'Systems this needs to integrate with';
        }
        if ($this->hasAnyFeature($features, ['Admin Panel']) && ! str_contains(strtolower(implode(' ', $features)), 'role')) {
            $missing[] = 'Admin permission structure';
        }

        return array_slice($missing, 0, 8);
    }

    // ---------- 6. Questions to Ask the Client ----------

    private function buildQuestions(array $missing): array
    {
        $questions = [];
        foreach ($missing as $requirement) {
            if (isset(self::REQUIREMENT_QUESTIONS[$requirement])) {
                $questions[] = self::REQUIREMENT_QUESTIONS[$requirement];
            }
        }

        return $questions;
    }

    // ---------- 7. Potential Risks / Blockers ----------

    private function identifyRisks(Lead $lead, array $services, array $features): array
    {
        $risks = [];

        $descriptionLength = strlen(trim($lead->business_description.' '.$lead->problems.' '.$lead->core_features));
        if ($descriptionLength < 40) {
            $risks[] = [
                'title' => 'Unclear requirements',
                'explanation' => 'The intake form has very little detail on the business problem or core features — scope could shift significantly once real requirements surface during discovery.',
            ];
        }

        if (count($services) >= 3) {
            $risks[] = [
                'title' => 'Changing scope',
                'explanation' => count($services).' services requested together ('.implode(', ', $services).') increases the chance that priorities or scope shift mid-project as the client sees early results.',
            ];
        }

        if ($this->hasAnyFeature($features, [...self::PAYMENT_FEATURES, 'Maps', 'Social Login', 'Push Notifications'])) {
            $risks[] = [
                'title' => 'Third-party API dependency',
                'explanation' => 'The requested features depend on external providers (payment, maps, social login, or push notifications) — delivery timing partly depends on those providers\' approval processes and uptime.',
            ];
        }

        if ($lead->timeline === 'ASAP (within 2 weeks)') {
            $risks[] = [
                'title' => 'Timeline constraints',
                'explanation' => 'A 2-week deadline is unrealistic for the estimated scope of '.implode(', ', $services).' — either the timeline needs to move or the initial scope needs to shrink.',
            ];
        }

        if (in_array($lead->budget, ['Under 30,000 ETB', 'To be discussed'], true) && array_intersect(['ERP System', 'Custom Software', 'AI Solution'], $services)) {
            $risks[] = [
                'title' => 'Budget mismatch',
                'explanation' => 'The stated budget looks tight for '.implode(', ', array_intersect(['ERP System', 'Custom Software', 'AI Solution'], $services)).' — expect a trade-off conversation during discovery.',
            ];
        }

        if (! $lead->design_style && empty($lead->reference_links)) {
            $risks[] = [
                'title' => 'Missing branding direction',
                'explanation' => 'No design style or reference links were provided — design work can\'t start efficiently until visual direction is confirmed, risking rework.',
            ];
        }

        if (in_array($lead->industry, ['Finance', 'Mining', 'Healthcare'], true)) {
            $risks[] = [
                'title' => 'Regulatory / compliance risk',
                'explanation' => "{$lead->industry} projects often carry data-handling or compliance requirements that should be scoped early to avoid late rework.",
            ];
        }

        $this->enrichRisksFromClientContext($lead, $risks);

        if (empty($risks)) {
            $risks[] = [
                'title' => 'Low overall risk',
                'explanation' => 'Scope, budget, and timeline appear well-aligned with no major red flags detected from the intake.',
            ];
        }

        return $risks;
    }

    /** Extra risk items derived from real Client/Project/Document data — only when the lead has already converted. */
    private function enrichRisksFromClientContext(Lead $lead, array &$risks): void
    {
        $client = $lead->convertedClient;
        if (! $client) {
            return;
        }

        $activeProjects = $client->activeProjects()->count();
        if ($activeProjects > 0) {
            $risks[] = [
                'title' => 'Existing engagement risk',
                'explanation' => "{$client->name} already has {$activeProjects} active project(s) — coordinate timeline and team availability before committing new dates.",
            ];
        }

        $outstanding = $client->documents()
            ->where('type', DocumentType::Invoice)
            ->whereIn('status', [DocumentStatus::Sent, DocumentStatus::PartiallyPaid, DocumentStatus::Overdue])
            ->count();
        if ($outstanding > 0) {
            $risks[] = [
                'title' => 'Outstanding invoice risk',
                'explanation' => "{$client->name} has {$outstanding} outstanding invoice(s) — consider addressing payment status before starting new scope.",
            ];
        }
    }

    // ---------- 8. Initial Quotation Range ----------

    /**
     * No internal price list, no internet access from this offline engine —
     * so the honest estimate is the client's own stated budget range, not a
     * fabricated number. GeminiAiEngine does the real market-pricing
     * research (see its buildPrompt()) and only falls back to this method
     * when the Gemini call itself fails.
     */
    private function buildQuotation(Lead $lead, array $services): array
    {
        $totalWeeks = array_sum(array_map(
            fn ($s) => self::SERVICE_WEEKS[$s] ?? self::DEFAULT_WEEKS,
            $services,
        ));

        $teamSize = match (true) {
            $totalWeeks >= 16 => '1 project manager, 3 developers, 1 designer, 1 QA',
            $totalWeeks >= 8 => '1 project manager, 2 developers, 1 designer',
            default => '1 developer-designer, part-time PM oversight',
        };

        $clientBounds = self::BUDGET_RANGE_BOUNDS[$lead->budget] ?? null;

        if (! $clientBounds) {
            return [
                'currency' => 'ETB',
                'range_low' => 0,
                'range_high' => 0,
                'reasoning' => [
                    'No confirmed budget range on file yet, and this offline fallback has no market-pricing data of its own to estimate from.',
                    'Confirm a budget range with the client, or configure GEMINI_API_KEY so the AI can research current real-world pricing for '.implode(', ', $services).'.',
                ],
            ];
        }

        return [
            'currency' => 'ETB',
            'range_low' => $clientBounds[0],
            'range_high' => $clientBounds[1],
            'reasoning' => [
                "Quoted directly from the client's stated budget range ({$lead->budget}) — this offline fallback has no access to real-world market pricing.",
                "Estimated team: {$teamSize}",
                "Estimated effort: {$totalWeeks} development-weeks across design, build, and QA",
                'For a market-researched estimate instead of the client\'s own stated range, configure GEMINI_API_KEY.',
            ],
        ];
    }

    // ---------- Shared helpers ----------

    /** @return array<int,string> */
    private function servicesOf(Lead $lead): array
    {
        return ! empty($lead->services) ? $lead->services : ['Website'];
    }

    /** Every selected feature across every service, flattened and deduplicated. */
    private function flattenFeatures(Lead $lead): array
    {
        $features = [];
        foreach ($lead->features ?? [] as $serviceFeatures) {
            foreach ((array) $serviceFeatures as $feature) {
                $features[] = $feature;
            }
        }

        return array_values(array_unique($features));
    }

    /** Normalizes free-text lead input into a clean sentence — avoids doubled punctuation like "..". */
    private function sentence(?string $text): string
    {
        $text = trim((string) $text);

        return $text === '' ? '' : rtrim($text, '.').'.';
    }

    private function hasAnyFeature(array $features, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (in_array($needle, $features, true)) {
                return true;
            }
        }

        return false;
    }
}

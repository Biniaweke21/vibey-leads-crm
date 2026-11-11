<?php

namespace App\Services\Ai;

use App\Contracts\AiEngineInterface;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Calls Google Gemini to produce the same 8-section analysis structure as
 * RuleBasedAiEngine, using `responseSchema` so Gemini is constrained to
 * return exactly that shape as JSON — no prompt-parsing heuristics needed.
 * Any failure (missing key, network error, malformed response) falls back
 * to RuleBasedAiEngine so the CRM's AI feature never hard-fails.
 */
class GeminiAiEngine implements AiEngineInterface
{
    private string $apiKey;

    private string $model;

    private string $lastUsedModel;

    public function __construct(private readonly RuleBasedAiEngine $fallback)
    {
        $this->apiKey = (string) config('services.gemini.api_key');
        $this->model = (string) config('services.gemini.model', 'gemini-2.0-flash');
        $this->lastUsedModel = $this->fallback->modelName();
    }

    public function modelName(): string
    {
        return $this->lastUsedModel;
    }

    public function analyzeLead(Lead $lead): array
    {
        try {
            if ($this->apiKey === '') {
                throw new \RuntimeException('GEMINI_API_KEY is not configured.');
            }

            $analysis = $this->callGemini($lead);
            $this->lastUsedModel = "gemini-{$this->model}";

            return $analysis;
        } catch (Throwable $e) {
            Log::error('Gemini AI analysis failed, falling back to rule-based engine.', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            $this->lastUsedModel = $this->fallback->modelName();

            return $this->fallback->analyzeLead($lead);
        }
    }

    private function callGemini(Lead $lead): array
    {
        $services = ! empty($lead->services) ? $lead->services : ['Website'];
        $pricingResearch = $this->researchMarketPricing($services);

        // Short timeouts so a slow/unreachable Google endpoint falls back to
        // the rule engine quickly instead of stalling the request (the whole
        // point of the fallback is that the CRM never hangs on the AI call).
        $response = Http::connectTimeout(5)->timeout(12)
            ->withHeaders(['x-goog-api-key' => $this->apiKey])
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent",
                [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $this->buildPrompt($lead, $pricingResearch)]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->responseSchema(),
                    ],
                ],
            )
            ->throw()
            ->json();

        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (! is_string($text)) {
            throw new \RuntimeException('Gemini response had no text part.');
        }

        $analysis = json_decode($text, true);
        if (! is_array($analysis)) {
            throw new \RuntimeException('Gemini response was not valid JSON.');
        }

        $required = ['project_summary', 'tech_stack', 'complexity', 'timeline', 'missing_requirements', 'questions', 'risks', 'quotation'];
        if (array_diff($required, array_keys($analysis)) !== []) {
            throw new \RuntimeException('Gemini response is missing required analysis sections.');
        }

        return $analysis;
    }

    /**
     * A separate, ungrounded-schema call with Google Search enabled so
     * Gemini can look up actual current market rates for these services
     * instead of guessing from training data. Gemini's API doesn't support
     * combining search grounding with a constrained JSON responseSchema in
     * one call, hence the two-step: research in plain text first, then feed
     * that as context into the schema-constrained analysis call below.
     * Soft-fails (returns null) on any error — the main analysis still
     * proceeds using the client's stated budget, just without live pricing
     * context, rather than aborting the whole analysis over a search hiccup.
     */
    private function researchMarketPricing(array $services): ?string
    {
        try {
            $prompt = 'Search for and summarize current, real-world market pricing (2026) for these kinds of '.
                'software projects, aimed at a small agency in Ethiopia pricing a client quote in ETB (Ethiopian Birr): '.
                implode(', ', $services).'. Give typical price ranges and briefly note what drives the low vs. high end. '.
                'Keep it to a few short paragraphs.';

            $response = Http::connectTimeout(5)->timeout(12)
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent",
                    [
                        'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                        'tools' => [['google_search' => new \stdClass]],
                        'generationConfig' => ['temperature' => 0.3],
                    ],
                )
                ->throw()
                ->json();

            return $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
        } catch (Throwable $e) {
            Log::warning('Gemini market-pricing research failed, continuing without it.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function buildPrompt(Lead $lead, ?string $pricingResearch): string
    {
        $features = [];
        foreach ($lead->features ?? [] as $serviceFeatures) {
            foreach ((array) $serviceFeatures as $feature) {
                $features[] = $feature;
            }
        }

        $facts = [
            'Business name' => $lead->business_name,
            'Industry' => $lead->industry,
            'City' => $lead->city,
            'Business description' => $lead->business_description,
            'Requested services' => implode(', ', $lead->services ?: ['Website']),
            'Selected features' => implode(', ', array_unique($features)) ?: 'None specified',
            'Project idea' => $lead->project_idea,
            'Main goal' => $lead->main_goal,
            'Target users' => $lead->target_users,
            'Problems to solve' => $lead->problems,
            'Core features requested' => $lead->core_features,
            'Design style' => $lead->design_style,
            'Budget' => $lead->budget,
            'Timeline' => $lead->timeline,
            'Existing website' => $lead->website_url,
            'Additional notes' => $lead->additional_notes,
        ];

        $lines = [];
        foreach ($facts as $label => $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $lines[] = "{$label}: {$value}";
            }
        }

        $pricingContext = $pricingResearch
            ? "Current real-world market pricing research for the requested services:\n{$pricingResearch}\n\n"
            : '';

        return 'You are a senior technical consultant at Vibey Worlds, a software agency in Ethiopia. '.
            'Analyze the following client intake for a software project and produce a structured requirement '.
            "analysis. Quote prices in ETB (Ethiopian Birr). Be specific and reference the client's actual ".
            "answers rather than generic advice.\n\n".
            $pricingContext.
            'For the quotation: do not invent a price from a fixed internal list. Base it on the real-world '.
            'market pricing research above (if provided) for these kinds of projects, scaled to this project\'s '.
            'actual scope and complexity. If the client stated a budget, the quotation range must never go '.
            "below it — treat their stated budget as a floor to build the quote from, not a ceiling to undercut. ".
            "Explain your reasoning against both the market research and the stated budget.\n\n".
            "Client intake:\n".implode("\n", $lines);
    }

    private function responseSchema(): array
    {
        $choiceWhy = [
            'type' => 'OBJECT',
            'properties' => [
                'choice' => ['type' => 'STRING'],
                'why' => ['type' => 'STRING'],
            ],
            'required' => ['choice', 'why'],
        ];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'project_summary' => ['type' => 'STRING'],
                'tech_stack' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'frontend' => $choiceWhy,
                        'backend' => $choiceWhy,
                        'database' => $choiceWhy,
                        'hosting' => $choiceWhy,
                        'authentication' => $choiceWhy,
                        'integrations' => $choiceWhy,
                    ],
                    'required' => ['frontend', 'backend', 'database', 'hosting', 'authentication', 'integrations'],
                ],
                'complexity' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'level' => ['type' => 'STRING', 'enum' => ['Low', 'Medium', 'High', 'Enterprise']],
                        'explanation' => ['type' => 'STRING'],
                    ],
                    'required' => ['level', 'explanation'],
                ],
                'timeline' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'total_duration' => ['type' => 'STRING'],
                        'phases' => [
                            'type' => 'ARRAY',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'phase' => ['type' => 'STRING'],
                                    'duration' => ['type' => 'STRING'],
                                ],
                                'required' => ['phase', 'duration'],
                            ],
                        ],
                    ],
                    'required' => ['total_duration', 'phases'],
                ],
                'missing_requirements' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'questions' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'risks' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'title' => ['type' => 'STRING'],
                            'explanation' => ['type' => 'STRING'],
                        ],
                        'required' => ['title', 'explanation'],
                    ],
                ],
                'quotation' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'currency' => ['type' => 'STRING'],
                        'range_low' => ['type' => 'INTEGER'],
                        'range_high' => ['type' => 'INTEGER'],
                        'reasoning' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    ],
                    'required' => ['currency', 'range_low', 'range_high', 'reasoning'],
                ],
            ],
            'required' => ['project_summary', 'tech_stack', 'complexity', 'timeline', 'missing_requirements', 'questions', 'risks', 'quotation'],
        ];
    }
}

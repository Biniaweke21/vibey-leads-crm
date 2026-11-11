<?php

namespace App\Contracts;

use App\Models\Lead;

/**
 * Swappable "brain" behind the AI Requirement Analyzer. `RuleBasedAiEngine`
 * is the only implementation for now (deterministic, no external API) —
 * bind a real `OpenAiEngine`/`ClaudeEngine`/etc. here later in
 * AppServiceProvider without touching AIController or AiService. Proposal
 * generation deliberately isn't part of this contract — it reuses the
 * structured analysis this produces rather than asking the model to draft
 * a proposal from scratch (see AiService::generateProposal()).
 */
interface AiEngineInterface
{
    /**
     * @return array{
     *   project_summary: string,
     *   tech_stack: array<string, array{choice: string, why: string}>,
     *   complexity: array{level: string, explanation: string},
     *   timeline: array{total_duration: string, phases: array<int, array{phase: string, duration: string}>},
     *   missing_requirements: array<int, string>,
     *   questions: array<int, string>,
     *   risks: array<int, array{title: string, explanation: string}>,
     *   quotation: array{currency: string, range_low: int, range_high: int, reasoning: array<int, string>}
     * }
     */
    public function analyzeLead(Lead $lead): array;

    /** Identifies which "model" produced the analysis, stored on the Lead alongside the report. */
    public function modelName(): string;
}

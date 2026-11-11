<?php

namespace App\Services;

use App\Contracts\AiEngineInterface;
use App\Enums\AiAnalysisStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The two-feature AI module: (1) generate/edit/save a structured
 * requirement analysis for a Lead, and (2) turn that analysis — plus the
 * Lead's own data — into a real Proposal Document. Proposal generation
 * deliberately never asks the AI engine to draft prose from scratch; it
 * assembles the proposal from data that's already been generated and
 * reviewed, then reuses DocumentService for storage (no duplicate document
 * logic).
 */
class AiService
{
    /** Vibey Worlds' standard payment schedule — not currently a configurable Setting. */
    private const PAYMENT_TERMS = [
        ['label' => 'Deposit', 'percentage' => 40],
        ['label' => 'Mid-Project', 'percentage' => 40],
        ['label' => 'Final Delivery', 'percentage' => 20],
    ];

    public function __construct(
        private readonly AiEngineInterface $engine,
        private readonly DocumentService $documents,
    ) {}

    public function analyze(Lead $lead): Lead
    {
        $lead->ai_analysis = $this->engine->analyzeLead($lead);
        $lead->ai_analysis_status = AiAnalysisStatus::Ready;
        $lead->save();

        $lead->activities()->create([
            'user_id' => auth()->id(),
            'description' => 'AI requirement analysis generated',
        ]);

        return $lead;
    }

    /** Persists a team member's manual edits to the stored analysis — no engine call. */
    public function updateAnalysis(Lead $lead, array $analysis): Lead
    {
        $lead->ai_analysis = $analysis;
        $lead->ai_analysis_status = AiAnalysisStatus::Ready;
        $lead->save();

        $lead->activities()->create([
            'user_id' => auth()->id(),
            'description' => 'AI analysis edited',
        ]);

        return $lead;
    }

    public function generateProposal(Lead $lead): Document
    {
        if (! $lead->converted_client_id) {
            throw ValidationException::withMessages([
                'lead' => ['This lead must be converted to a client before generating a proposal.'],
            ]);
        }

        if (! $lead->ai_analysis) {
            throw ValidationException::withMessages([
                'lead' => ['Run the AI requirement analysis before generating a proposal.'],
            ]);
        }

        return DB::transaction(function () use ($lead) {
            $analysis = $lead->ai_analysis;
            $services = ! empty($lead->services) ? $lead->services : ['Website'];
            $quotation = $analysis['quotation'] ?? ['range_low' => 0, 'range_high' => 0];
            $midpoint = (int) round((($quotation['range_low'] ?? 0) + ($quotation['range_high'] ?? 0)) / 2);

            $document = $this->documents->create([
                'type' => DocumentType::Proposal->value,
                'client_id' => $lead->converted_client_id,
                'project_id' => $lead->project?->id,
                'title' => "Proposal for {$lead->business_name}",
                'body' => [
                    'executive_summary' => $analysis['project_summary'] ?? '',
                    'scope_of_work' => $this->buildScopeOfWork($lead, $services),
                    'deliverables' => array_map(fn ($s) => "{$s} — fully implemented and tested", $services),
                    'tech_stack' => $analysis['tech_stack'] ?? [],
                    'timeline' => $analysis['timeline'] ?? [],
                    'quotation' => $quotation,
                    'assumptions' => $this->buildAssumptions($analysis['missing_requirements'] ?? []),
                    'payment_terms' => self::PAYMENT_TERMS,
                    'call_to_action' => "We're excited about the opportunity to work with {$lead->business_name} on this project. ".
                        'This proposal is valid for 30 days — reach out any time to confirm scope and schedule a kickoff call.',
                ],
            ]);

            $this->documents->addLineItem($document, [
                'description' => 'Estimated project cost — '.implode(', ', $services),
                'quantity' => 1,
                'unit_price' => $midpoint,
            ]);

            $lead->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Proposal generated from AI analysis: {$document->title}",
            ]);

            return $document->fresh(['lineItems', 'payments']);
        });
    }

    /** @param array<int,string> $services */
    private function buildScopeOfWork(Lead $lead, array $services): string
    {
        $features = [];
        foreach ($lead->features ?? [] as $serviceFeatures) {
            foreach ((array) $serviceFeatures as $feature) {
                $features[] = $feature;
            }
        }
        $features = array_values(array_unique($features));

        $text = 'Vibey Worlds will design, build, and deliver '.implode(', ', $services)." for {$lead->business_name}.";
        if ($features) {
            $text .= ' The build includes: '.implode(', ', $features).'.';
        }
        if ($lead->problems) {
            $text .= ' This directly addresses: '.rtrim($lead->problems, '.').'.';
        }

        return $text;
    }

    /** @param array<int,string> $missingRequirements */
    private function buildAssumptions(array $missingRequirements): array
    {
        return array_map(
            fn ($requirement) => "It is assumed that \"{$requirement}\" will be finalized during discovery — pricing and timeline may adjust once confirmed.",
            $missingRequirements,
        );
    }
}

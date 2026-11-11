<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\UpdateAiAnalysisRequest;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;

class AIController extends Controller
{
    public function __construct(private readonly AiService $ai) {}

    /**
     * POST /api/leads/{lead}/ai/analyze — generates (or regenerates) the
     * structured requirement analysis and stores it on the Lead.
     * Authorization: LeadPolicy::view (any internal role).
     */
    public function analyze(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        $lead = $this->ai->analyze($lead);

        return $this->success(new LeadResource($lead), 'AI requirement analysis generated successfully.');
    }

    /**
     * PUT/PATCH /api/leads/{lead}/ai/analysis — saves manual edits to the
     * stored analysis.
     * Authorization: LeadPolicy::update (checked in UpdateAiAnalysisRequest).
     */
    public function updateAnalysis(UpdateAiAnalysisRequest $request, Lead $lead): JsonResponse
    {
        $lead = $this->ai->updateAnalysis($lead, $request->validated());

        return $this->success(new LeadResource($lead), 'AI analysis saved successfully.');
    }

    /**
     * POST /api/leads/{lead}/ai/proposal — assembles a Proposal Document
     * from the Lead's data and its stored AI analysis, and stores it in
     * the Documents module.
     * Authorization: LeadPolicy::update (mutates via DocumentService).
     */
    public function generateProposal(Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $document = $this->ai->generateProposal($lead);

        return $this->success(new DocumentResource($document), 'Proposal generated successfully.', 201);
    }
}

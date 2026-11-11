<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\AnalyticsRequest;
use App\Http\Resources\ActivityFeedResource;
use App\Http\Resources\AnalyticsDashboardResource;
use App\Http\Resources\ChartResource;
use App\Http\Resources\TeamPerformanceResource;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

/**
 * Read-only — every endpoint computes live from existing tables via
 * AnalyticsService. No AnalyticsPolicy: the `role:admin,sales,project_manager`
 * route-group middleware is the only gate (same pattern as AI Workspace history).
 */
class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function dashboard(AnalyticsRequest $request): JsonResponse
    {
        return $this->success(new AnalyticsDashboardResource($this->analytics->dashboard($request->validated())));
    }

    public function revenueTrend(AnalyticsRequest $request): JsonResponse
    {
        return $this->success(ChartResource::collection($this->analytics->revenueTrend($request->validated())));
    }

    public function leadsTrend(AnalyticsRequest $request): JsonResponse
    {
        return $this->success(ChartResource::collection($this->analytics->leadsTrend($request->validated())));
    }

    public function pipeline(AnalyticsRequest $request): JsonResponse
    {
        return $this->success(ChartResource::collection($this->analytics->pipeline($request->validated())));
    }

    public function services(AnalyticsRequest $request): JsonResponse
    {
        return $this->success(ChartResource::collection($this->analytics->serviceDistribution($request->validated())));
    }

    public function projectStatus(AnalyticsRequest $request): JsonResponse
    {
        return $this->success(ChartResource::collection($this->analytics->projectStatusDistribution($request->validated())));
    }

    public function revenueByClient(AnalyticsRequest $request): JsonResponse
    {
        return $this->success(ChartResource::collection($this->analytics->revenueByClient($request->validated())));
    }

    public function proposals(AnalyticsRequest $request): JsonResponse
    {
        return $this->success(ChartResource::collection($this->analytics->proposalSuccess($request->validated())));
    }

    public function invoices(AnalyticsRequest $request): JsonResponse
    {
        return $this->success(ChartResource::collection($this->analytics->invoiceStatus($request->validated())));
    }

    public function teamPerformance(AnalyticsRequest $request): JsonResponse
    {
        return $this->success(TeamPerformanceResource::collection($this->analytics->teamPerformance($request->validated())));
    }

    public function activity(AnalyticsRequest $request): JsonResponse
    {
        return $this->success(ActivityFeedResource::collection($this->analytics->recentActivity($request->validated())));
    }
}

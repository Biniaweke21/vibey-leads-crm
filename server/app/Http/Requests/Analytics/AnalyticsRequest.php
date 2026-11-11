<?php

namespace App\Http\Requests\Analytics;

use App\Http\Requests\Concerns\ValidatesIncludes;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Single shared Form Request for every Analytics endpoint — the spec
 * requires "the same filter must work across every Analytics endpoint",
 * so one query contract (rather than 11 near-duplicate Index-style
 * classes) is the more faithful reading. Each AnalyticsService method
 * applies only the subset of these filters that are meaningful for its
 * own query; unused keys are simply ignored (same tolerant pattern every
 * other Service already uses for optional filters).
 */
class AnalyticsRequest extends FormRequest
{
    use ValidatesIncludes;

    /** No AnalyticsPolicy exists by design — the `role:` route middleware is the only gate. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Date range — preset or custom.
            'range' => ['sometimes', Rule::in(['7d', '30d', '90d', 'ytd', 'custom'])],
            'from' => ['required_if:range,custom', 'date'],
            'to' => ['required_if:range,custom', 'date', 'after_or_equal:from'],

            // Trend grouping (revenue-trend, leads-trend).
            'group' => ['sometimes', Rule::in(['day', 'week', 'month'])],

            // Cross-endpoint filters — applied where meaningful by the Service.
            'assigned_to' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'client_id' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'project_id' => ['sometimes', 'string', 'regex:/^\d+(,\d+)*$/'],
            'service' => ['sometimes', 'string', $this->csvInRule(Lead::SERVICE_OPTIONS)],
            'industry' => ['sometimes', 'string', 'max:255'],

            // Sorting (revenue-by-client, team-performance).
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],

            // Row cap (revenue-by-client, recent activity).
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}

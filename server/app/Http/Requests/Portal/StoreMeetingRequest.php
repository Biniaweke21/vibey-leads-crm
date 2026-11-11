<?php

namespace App\Http\Requests\Portal;

use App\Models\Project;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Self-service meeting booking from the client portal. `client_id` and
 * `organizer_id` are never accepted here — ClientPortalService always
 * forces client_id to the authenticated user's own client, so a client can
 * never book a meeting against a company that isn't theirs.
 */
class StoreMeetingRequest extends FormRequest
{
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id', $this->projectMustBelongToClient()],
        ];
    }

    private function projectMustBelongToClient(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null) {
                return;
            }
            $project = Project::find($value);
            if (! $project || $project->client_id !== $this->user()->client_id) {
                $fail('The selected project does not belong to your account.');
            }
        };
    }
}

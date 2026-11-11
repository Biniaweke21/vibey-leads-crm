<?php

namespace App\Http\Requests\Portal;

use App\Enums\DesignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A client may only decide pending -> approved/rejected, never rename/replace the file. Ownership of {project}/{designSubmission} is checked in ClientPortalService (404, not 403, per the portal's IDOR requirement). */
class ReviewDesignSubmissionRequest extends FormRequest
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
            'status' => ['required', Rule::enum(DesignStatus::class)->only([DesignStatus::Approved, DesignStatus::Rejected])],
            'note' => ['sometimes', 'nullable', 'string'],
        ];
    }
}

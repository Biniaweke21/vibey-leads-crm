<?php

namespace App\Http\Requests\Project;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Managing a project's tasks is gated by the same "update this project" ability.
        return $this->user()->can('update', $this->route('project'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id', $this->assigneeMustBeTeamMember()],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'due_date' => ['nullable', 'date'],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
        ];
    }

    private function assigneeMustBeTeamMember(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null) {
                return;
            }
            $user = User::find($value);
            if ($user && $user->role === UserRole::Client) {
                $fail('The assigned user must be a team member, not a client.');
            }
        };
    }
}

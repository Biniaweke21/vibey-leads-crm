<?php

namespace App\Http\Requests\Project;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class AssignMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id', function (string $attribute, mixed $value, Closure $fail): void {
                $user = User::find($value);
                if ($user && $user->role === UserRole::Client) {
                    $fail('A project member must be a team member, not a client.');
                }
            }],
        ];
    }
}

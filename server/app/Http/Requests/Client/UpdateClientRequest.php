<?php

namespace App\Http\Requests\Client;

use App\Enums\ClientStatus;
use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', Rule::enum(ClientStatus::class)],
            'account_manager_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id', $this->accountManagerMustBeTeamMember()],
            'last_interaction_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    private function accountManagerMustBeTeamMember(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null) {
                return;
            }
            $user = User::find($value);
            if ($user && $user->role === UserRole::Client) {
                $fail('The account manager must be a team member, not a client.');
            }
        };
    }
}

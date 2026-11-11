<?php

namespace App\Http\Requests\Client;

use App\Enums\ClientStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Client::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::enum(ClientStatus::class)],
            'account_manager_id' => ['nullable', 'integer', 'exists:users,id', $this->accountManagerMustBeTeamMember()],
            'last_interaction_at' => ['nullable', 'date'],
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

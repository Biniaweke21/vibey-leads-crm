<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\TeamInvitationMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Directory of internal team members — needed anywhere the frontend must
 * let a user pick an assignee/attendee/team member by a real user_id
 * (Leads assignment, Meeting attendees, Project team), plus Settings' Team
 * page (invite/role-change/remove). store() creates the account with a
 * real generated password and emails it directly so the new member can log
 * in immediately (see AuthController::updatePassword() for changing it
 * afterward). Whether that email actually leaves the server depends on
 * MAIL_MAILER in .env (defaults to "log" — written to
 * storage/logs/laravel.log, not really sent).
 */
class TeamController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success(UserResource::collection($this->teamQuery()->get()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_map(fn (UserRole $r) => $r->value, UserRole::team()))],
        ]);

        $password = Str::password(12);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make($password),
        ]);

        try {
            Mail::to($user->email)->send(new TeamInvitationMail($user, $password));
        } catch (\Throwable $e) {
            Log::warning("Could not email login credentials to {$user->email}: {$e->getMessage()}");

            return $this->success(
                new UserResource($user),
                "Team member created, but the credentials email could not be sent. Their password is: {$password}",
                201,
            );
        }

        return $this->success(new UserResource($user), 'Team member invited successfully — their login credentials have been emailed.', 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_if($user->role === UserRole::Client, 404, 'Resource not found.');

        $data = $request->validate([
            'role' => ['required', Rule::in(array_map(fn (UserRole $r) => $r->value, UserRole::team()))],
        ]);

        $user->update($data);

        return $this->success(new UserResource($user), 'Role updated successfully.');
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if($user->role === UserRole::Client, 404, 'Resource not found.');

        $user->delete();

        return $this->success(null, 'Team member removed successfully.');
    }

    private function teamQuery()
    {
        return User::query()
            ->whereIn('role', array_map(fn (UserRole $r) => $r->value, UserRole::team()))
            ->orderBy('name');
    }
}

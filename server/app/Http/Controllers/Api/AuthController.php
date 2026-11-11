<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ClientOnboardingService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly ClientOnboardingService $onboarding) {}

    /**
     * POST /api/login — issue a Sanctum bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->role === UserRole::Client && ! $user->portal_enabled) {
            throw ValidationException::withMessages([
                'email' => ['Your portal access has been disabled. Contact your account manager.'],
            ]);
        }

        $token = $user->createToken($request->input('device_name', 'api'))->plainTextToken;

        $this->onboarding->recordLogin($user);

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Logged in successfully.');
    }

    /**
     * POST /api/logout — revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * GET /api/user — the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    /**
     * POST /api/forgot-password — always responds success (never reveals
     * whether the email exists) and emails a reset link when it does. Same
     * flow a new team member's invite uses to set their first password.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return $this->success(null, 'If that email exists, a reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->success(null, 'Password updated successfully.');
    }

    /**
     * PUT /api/user/signature — for a signed-in user to set the signature
     * image (uploaded separately via POST /uploads) attached to documents
     * they send, when they choose to include it.
     */
    public function updateSignature(Request $request): JsonResponse
    {
        $data = $request->validate([
            'signature_url' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $user->signature_url = $data['signature_url'] ?? null;
        $user->save();

        return $this->success(new UserResource($user), 'Signature updated successfully.');
    }

    /**
     * PUT /api/user/password — for a signed-in user to change their own
     * password (e.g. after logging in with an emailed team-invite password).
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->validated('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->validated('password')),
            'must_change_password' => false,
        ])->save();

        if ($user->role === UserRole::Client && $user->client) {
            $user->client->activities()->create([
                'user_id' => $user->id,
                'description' => 'Password changed',
            ]);
        }

        return $this->success(null, 'Password updated successfully.');
    }
}

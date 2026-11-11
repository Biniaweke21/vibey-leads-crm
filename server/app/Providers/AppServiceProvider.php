<?php

namespace App\Providers;

use App\Contracts\AiEngineInterface;
use App\Mail\ResetPasswordMail;
use App\Services\Ai\GeminiAiEngine;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // GeminiAiEngine calls the real Gemini API and falls back to
        // RuleBasedAiEngine on any failure — AIController/AIService never
        // need to change to swap the underlying engine.
        $this->app->bind(AiEngineInterface::class, GeminiAiEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Points the password-reset link at the Vue SPA (not a Blade view —
        // this backend has no server-rendered password-reset page) and
        // sends the branded ResetPasswordMail instead of Laravel's default
        // notification template. Team roles land on the team reset page,
        // Client role lands on the client portal's reset page — same API
        // endpoint either way, only the SPA route differs.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $frontendUrl = rtrim(config('services.frontend.url', 'http://localhost:5173'), '/');
            $path = $notifiable->role?->isTeam() !== false ? '/reset-password' : '/client/reset-password';
            $url = "{$frontendUrl}{$path}?token={$token}&email=".urlencode($notifiable->getEmailForPasswordReset());

            return new ResetPasswordMail($notifiable, $url);
        });
    }
}

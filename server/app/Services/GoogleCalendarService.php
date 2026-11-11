<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * One shared company Google account (connected once by an Admin in
 * Settings) used to create real Google Meet links for booked meetings.
 *
 * ponytail: plain REST + Laravel's HTTP client, same call as
 * CloudinaryService — the OAuth dance + one Calendar API endpoint doesn't
 * warrant pulling in the full google/apiclient SDK.
 */
class GoogleCalendarService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const EVENTS_URL = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

    private const SCOPE = 'https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/userinfo.email';

    public function authUrl(): string
    {
        $params = [
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect_uri'),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return self::AUTH_URL.'?'.http_build_query($params);
    }

    public function isConnected(): bool
    {
        return ! empty(Setting::get('google_calendar', [])['refresh_token'] ?? null);
    }

    public function connectedEmail(): ?string
    {
        return Setting::get('google_calendar', [])['email'] ?? null;
    }

    public function disconnect(): void
    {
        Setting::put('google_calendar', []);
    }

    /** Exchanges the OAuth callback's ?code= for tokens and stores them. */
    public function handleCallback(string $code): void
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect_uri'),
            'grant_type' => 'authorization_code',
        ])->throw()->json();

        $email = Http::withToken($response['access_token'])
            ->get('https://www.googleapis.com/oauth2/v2/userinfo')
            ->json('email');

        Setting::put('google_calendar', [
            'refresh_token' => $response['refresh_token'] ?? Setting::get('google_calendar', [])['refresh_token'] ?? null,
            'access_token' => $response['access_token'],
            'expires_at' => now()->addSeconds($response['expires_in'])->toIso8601String(),
            'email' => $email,
        ]);
    }

    /**
     * Creates a Calendar event with a Google Meet link for the given
     * meeting and returns the hangout link, or null if Google isn't
     * connected or the API call fails — never throws, since a failed Meet
     * link shouldn't block booking the meeting itself.
     */
    public function createMeetingEvent(Meeting $meeting): ?string
    {
        $token = $this->freshAccessToken();
        if (! $token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->post(self::EVENTS_URL.'?conferenceDataVersion=1', [
                    'summary' => $meeting->title,
                    'description' => $meeting->description,
                    'start' => ['dateTime' => $meeting->starts_at->toRfc3339String(), 'timeZone' => $meeting->timezone ?? config('app.timezone')],
                    'end' => ['dateTime' => ($meeting->ends_at ?? $meeting->starts_at->copy()->addMinutes(30))->toRfc3339String(), 'timeZone' => $meeting->timezone ?? config('app.timezone')],
                    'conferenceData' => [
                        'createRequest' => [
                            'requestId' => (string) Str::uuid(),
                            'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                        ],
                    ],
                ])
                ->throw()
                ->json();

            return $response['hangoutLink'] ?? null;
        } catch (\Throwable $e) {
            Log::warning("Could not create a Google Meet link for meeting #{$meeting->id}: {$e->getMessage()}");

            return null;
        }
    }

    /** Returns a valid access token, refreshing it first if it's expired. */
    private function freshAccessToken(): ?string
    {
        $tokens = Setting::get('google_calendar', []);
        if (empty($tokens['refresh_token'])) {
            return null;
        }

        if (! empty($tokens['access_token']) && now()->lt($tokens['expires_at'] ?? now())) {
            return $tokens['access_token'];
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'grant_type' => 'refresh_token',
        ])->throw()->json();

        $tokens['access_token'] = $response['access_token'];
        $tokens['expires_at'] = now()->addSeconds($response['expires_in'])->toIso8601String();
        Setting::put('google_calendar', $tokens);

        return $tokens['access_token'];
    }
}

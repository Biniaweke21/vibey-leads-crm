<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thin wrapper over the existing key-value `Setting` model (company,
 * notifications) — no dedicated Service/FormRequest layer, since each
 * action is a single trivial get-or-put with inline validation (a new
 * module's worth of scaffolding isn't warranted for this).
 */
class SettingsController extends Controller
{
    public function __construct(private readonly GoogleCalendarService $googleCalendar) {}

    public function company(): JsonResponse
    {
        return $this->success(Setting::get('company', []));
    }

    public function updateCompany(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            // Used to brand generated PDFs (proposals/contracts/invoices).
            'logo_url' => ['nullable', 'string', 'max:500'],
            'primary_color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // The one company stamp, stamped onto documents when a sender opts in.
            'stamp_url' => ['nullable', 'string', 'max:500'],
        ]);

        Setting::put('company', $data);

        return $this->success($data, 'Company information updated successfully.');
    }

    public function notifications(): JsonResponse
    {
        return $this->success(Setting::get('notifications', []));
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $data = $request->validate([
            'newLeads' => ['required', 'boolean'],
            'invoicePaid' => ['required', 'boolean'],
            'meetingReminders' => ['required', 'boolean'],
            'weeklyDigest' => ['required', 'boolean'],
            'productUpdates' => ['required', 'boolean'],
        ]);

        Setting::put('notifications', $data);

        return $this->success($data, 'Notification preferences updated successfully.');
    }

    // ---------- Google Calendar (shared company account) ----------

    public function googleStatus(): JsonResponse
    {
        return $this->success([
            'connected' => $this->googleCalendar->isConnected(),
            'email' => $this->googleCalendar->connectedEmail(),
        ]);
    }

    /** Admin clicks "Connect" — returns the consent URL for the frontend to navigate to. */
    public function googleConnect(): JsonResponse
    {
        return $this->success(['url' => $this->googleCalendar->authUrl()]);
    }

    /**
     * GET /api/settings/google/callback — Google redirects the browser
     * here after consent, with no auth header of its own (it's a plain
     * top-level navigation, not an XHR the SPA can attach a token to).
     * The `connect` step above is what's actually gated to admins.
     */
    public function googleCallback(Request $request): RedirectResponse
    {
        $frontendUrl = rtrim(config('services.frontend.url'), '/');

        if (! $request->string('code')->trim()->isNotEmpty()) {
            return redirect("{$frontendUrl}/settings?google=error");
        }

        $this->googleCalendar->handleCallback($request->string('code')->toString());

        return redirect("{$frontendUrl}/settings?google=connected");
    }

    public function googleDisconnect(): JsonResponse
    {
        $this->googleCalendar->disconnect();

        return $this->success(null, 'Google Calendar disconnected.');
    }
}

<?php

use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientPortalController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes  (all prefixed with /api)
|--------------------------------------------------------------------------
| Remaining business-module routes (analytics, settings, ai) are added in
| later phases — see docs/API_STRUCTURE.md for the full planned catalog.
*/

// Public
Route::post('/login', [AuthController::class, 'login']);
// The landing page's anonymous "Request a Quote" intake — was previously
// (incorrectly) only reachable inside the authenticated team group, which
// made public lead submission impossible. See StoreLeadRequest::authorize().
Route::post('/leads', [LeadController::class, 'store']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
// Also public — an anonymous visitor attaching a logo/brand file to the
// request form above needs to reach Cloudinary before any account exists.
// UploadController never touches auth() itself, so this is safe either way.
Route::post('uploads', [UploadController::class, 'store']);
// Public — Google redirects the browser here after consent with no bearer
// token of its own. The step that actually starts the flow (google/connect,
// below) is Admin-gated; this just completes whatever that step started.
Route::get('settings/google/callback', [SettingsController::class, 'googleCallback']);

// Authenticated (Sanctum bearer token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);
    Route::put('/user/signature', [AuthController::class, 'updateSignature']);

    // Leads / Clients / Projects / Documents — internal team only (Admin,
    // Team Member); Clients (the role) never see internal pipeline/account
    // data. Fine-grained authorization (e.g. delete = Admin only) is
    // enforced by the respective Policy on top of this coarse gate.
    Route::middleware('role:admin,team_member')->group(function () {
        // Team directory (read) is needed by every team role for
        // assignee/attendee/member pickers — mutations (invite/role
        // change/remove) and Company settings are Admin-only.
        Route::get('team', [TeamController::class, 'index']);
        Route::middleware('role:admin')->group(function () {
            Route::post('team', [TeamController::class, 'store']);
            Route::match(['put', 'patch'], 'team/{user}', [TeamController::class, 'update']);
            Route::delete('team/{user}', [TeamController::class, 'destroy']);
        });

        Route::apiResource('leads', LeadController::class)->except(['store']);
        Route::post('leads/{lead}/convert', [LeadController::class, 'convert']);
        Route::post('leads/{lead}/notes', [LeadController::class, 'storeNote']);
        Route::match(['put', 'patch'], 'leads/{lead}/notes/{note}', [LeadController::class, 'updateNote']);
        Route::delete('leads/{lead}/notes/{note}', [LeadController::class, 'destroyNote']);
        Route::post('leads/{lead}/reminders', [LeadController::class, 'storeReminder']);
        Route::post('leads/{lead}/attachments', [LeadController::class, 'storeAttachment']);
        Route::delete('leads/{lead}/attachments/{attachment}', [LeadController::class, 'destroyAttachment']);

        Route::get('reminders', [ReminderController::class, 'index']);
        Route::match(['put', 'patch'], 'reminders/{reminder}', [ReminderController::class, 'update']);
        Route::delete('reminders/{reminder}', [ReminderController::class, 'destroy']);

        Route::apiResource('clients', ClientController::class);
        Route::post('clients/{client}/contacts', [ClientController::class, 'storeContact']);
        Route::match(['put', 'patch'], 'clients/{client}/contacts/{contact}', [ClientController::class, 'updateContact']);
        Route::post('clients/{client}/notes', [ClientController::class, 'storeNote']);
        Route::match(['put', 'patch'], 'clients/{client}/notes/{note}', [ClientController::class, 'updateNote']);
        Route::delete('clients/{client}/notes/{note}', [ClientController::class, 'destroyNote']);
        Route::post('clients/{client}/attachments', [ClientController::class, 'storeAttachment']);
        Route::delete('clients/{client}/attachments/{attachment}', [ClientController::class, 'destroyAttachment']);

        // Client Portal account management — Admin only.
        Route::middleware('role:admin')->group(function () {
            Route::post('clients/{client}/portal/enable', [ClientController::class, 'enablePortal']);
            Route::post('clients/{client}/portal/disable', [ClientController::class, 'disablePortal']);
            Route::post('clients/{client}/portal/reset-password', [ClientController::class, 'resetPortalPassword']);
            Route::post('clients/{client}/portal/regenerate-password', [ClientController::class, 'regeneratePortalPassword']);
            Route::post('clients/{client}/portal/resend-welcome', [ClientController::class, 'resendWelcomeEmail']);
        });

        Route::apiResource('projects', ProjectController::class);

        Route::post('projects/{project}/tasks', [ProjectController::class, 'storeTask']);
        Route::match(['put', 'patch'], 'projects/{project}/tasks/{task}', [ProjectController::class, 'updateTask']);
        Route::delete('projects/{project}/tasks/{task}', [ProjectController::class, 'destroyTask']);

        Route::post('projects/{project}/milestones', [ProjectController::class, 'storeMilestone']);
        Route::match(['put', 'patch'], 'projects/{project}/milestones/{milestone}', [ProjectController::class, 'updateMilestone']);
        Route::delete('projects/{project}/milestones/{milestone}', [ProjectController::class, 'destroyMilestone']);

        Route::post('projects/{project}/design-submissions', [ProjectController::class, 'storeDesignSubmission']);
        Route::match(['put', 'patch'], 'projects/{project}/design-submissions/{designSubmission}', [ProjectController::class, 'updateDesignSubmission']);
        Route::delete('projects/{project}/design-submissions/{designSubmission}', [ProjectController::class, 'destroyDesignSubmission']);

        Route::post('projects/{project}/members', [ProjectController::class, 'storeMember']);
        Route::delete('projects/{project}/members/{user}', [ProjectController::class, 'destroyMember']);

        Route::post('projects/{project}/attachments', [ProjectController::class, 'storeAttachment']);
        Route::delete('projects/{project}/attachments/{attachment}', [ProjectController::class, 'destroyAttachment']);

        Route::apiResource('documents', DocumentController::class);

        Route::post('documents/{document}/line-items', [DocumentController::class, 'storeLineItem']);
        Route::match(['put', 'patch'], 'documents/{document}/line-items/{lineItem}', [DocumentController::class, 'updateLineItem']);
        Route::delete('documents/{document}/line-items/{lineItem}', [DocumentController::class, 'destroyLineItem']);

        Route::post('documents/{document}/payments', [DocumentController::class, 'storePayment']);
        Route::match(['put', 'patch'], 'documents/{document}/payments/{payment}', [DocumentController::class, 'updatePayment']);
        Route::delete('documents/{document}/payments/{payment}', [DocumentController::class, 'destroyPayment']);

        Route::post('documents/{document}/duplicate', [DocumentController::class, 'duplicate']);
        Route::post('documents/{document}/approve', [DocumentController::class, 'approve']);
        Route::post('documents/{document}/reject', [DocumentController::class, 'reject']);
        Route::post('documents/{document}/convert-to-contract', [DocumentController::class, 'convertToContract']);
        Route::post('documents/{document}/send', [DocumentController::class, 'send']);
        Route::post('documents/{document}/send-reminder', [DocumentController::class, 'sendReminder']);
        Route::post('documents/{document}/sign', [DocumentController::class, 'sign']);
        Route::post('documents/{document}/expire', [DocumentController::class, 'expire']);
        Route::post('documents/{document}/convert-to-invoice', [DocumentController::class, 'convertToInvoice']);
        Route::get('documents/{document}/pdf', [DocumentController::class, 'downloadPdf']);

        Route::apiResource('meetings', MeetingController::class);

        Route::post('meetings/{meeting}/attendees', [MeetingController::class, 'storeAttendee']);
        Route::match(['put', 'patch'], 'meetings/{meeting}/attendees/{attendee}', [MeetingController::class, 'updateAttendee']);
        Route::delete('meetings/{meeting}/attendees/{attendee}', [MeetingController::class, 'destroyAttendee']);
        Route::post('meetings/{meeting}/notes', [MeetingController::class, 'storeNote']);
        Route::match(['put', 'patch'], 'meetings/{meeting}/notes/{note}', [MeetingController::class, 'updateNote']);
        Route::delete('meetings/{meeting}/notes/{note}', [MeetingController::class, 'destroyNote']);
        Route::post('meetings/{meeting}/attachments', [MeetingController::class, 'storeAttachment']);
        Route::delete('meetings/{meeting}/attachments/{attachment}', [MeetingController::class, 'destroyAttachment']);

        // AI — two capabilities only: analyze a lead's requirements, and
        // turn that analysis into a stored Proposal Document.
        Route::post('leads/{lead}/ai/analyze', [AIController::class, 'analyze']);
        Route::match(['put', 'patch'], 'leads/{lead}/ai/analysis', [AIController::class, 'updateAnalysis']);
        Route::post('leads/{lead}/ai/proposal', [AIController::class, 'generateProposal']);

        // Analytics — read-only, computed live from existing tables. Same
        // filter shape (range/from/to/...) accepted by every endpoint.
        Route::prefix('analytics')->group(function () {
            Route::get('dashboard', [AnalyticsController::class, 'dashboard']);
            Route::get('revenue-trend', [AnalyticsController::class, 'revenueTrend']);
            Route::get('leads-trend', [AnalyticsController::class, 'leadsTrend']);
            Route::get('pipeline', [AnalyticsController::class, 'pipeline']);
            Route::get('services', [AnalyticsController::class, 'services']);
            Route::get('project-status', [AnalyticsController::class, 'projectStatus']);
            Route::get('revenue-by-client', [AnalyticsController::class, 'revenueByClient']);
            Route::get('proposals', [AnalyticsController::class, 'proposals']);
            Route::get('invoices', [AnalyticsController::class, 'invoices']);
            Route::get('team-performance', [AnalyticsController::class, 'teamPerformance']);
            Route::get('activity', [AnalyticsController::class, 'activity']);
        });

        // Settings — thin wrapper over the existing Setting key-value store.
        // Billing/branding/AI settings have no backend equivalent and are
        // intentionally left off this list (frontend-only stubs — see Phase
        // 11 summary). Company and the Google Calendar connection are
        // Admin-only; Notifications and the connection status read are open
        // to every team role.
        Route::prefix('settings')->group(function () {
            Route::middleware('role:admin')->group(function () {
                Route::get('company', [SettingsController::class, 'company']);
                Route::put('company', [SettingsController::class, 'updateCompany']);
                Route::get('google/connect', [SettingsController::class, 'googleConnect']);
                Route::post('google/disconnect', [SettingsController::class, 'googleDisconnect']);
            });
            Route::get('notifications', [SettingsController::class, 'notifications']);
            Route::put('notifications', [SettingsController::class, 'updateNotifications']);
            Route::get('google/status', [SettingsController::class, 'googleStatus']);
        });
    });

    // Client Portal — read-only except profile updates, uploading a project
    // attachment, and booking a meeting. Every resource is scoped to the
    // authenticated client user's own client_id inside ClientPortalService;
    // cross-tenant access resolves to 404, not 403.
    Route::middleware('role:client')->prefix('portal')->group(function () {
        Route::get('dashboard', [ClientPortalController::class, 'dashboard']);

        Route::get('projects', [ClientPortalController::class, 'projects']);
        Route::get('projects/{project}', [ClientPortalController::class, 'showProject']);
        Route::post('projects/{project}/attachments', [ClientPortalController::class, 'storeProjectAttachment']);
        Route::match(['put', 'patch'], 'projects/{project}/design-submissions/{designSubmission}/review', [ClientPortalController::class, 'reviewDesign']);

        Route::get('documents', [ClientPortalController::class, 'documents']);
        Route::get('documents/{document}', [ClientPortalController::class, 'showDocument']);
        Route::get('documents/{document}/pdf', [ClientPortalController::class, 'downloadDocumentPdf']);
        Route::post('documents/{document}/approve', [ClientPortalController::class, 'approveDocument']);
        Route::post('documents/{document}/reject', [ClientPortalController::class, 'rejectDocument']);
        Route::post('documents/{document}/payments', [ClientPortalController::class, 'storePaymentProof']);

        Route::get('meetings', [ClientPortalController::class, 'meetings']);
        Route::post('meetings', [ClientPortalController::class, 'storeMeeting']);
        Route::get('meetings/{meeting}', [ClientPortalController::class, 'showMeeting']);
        Route::post('meetings/{meeting}/respond', [ClientPortalController::class, 'respondMeeting']);

        Route::get('timeline', [ClientPortalController::class, 'timeline']);

        Route::get('profile', [ClientPortalController::class, 'profile']);
        Route::match(['put', 'patch'], 'profile', [ClientPortalController::class, 'updateProfile']);
    });
});

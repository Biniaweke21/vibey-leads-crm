<?php

namespace App\Services;

use App\Enums\ClientStatus;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Mail\ClientWelcomeMail;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Converts a Lead into a Client — creating the Client, its first Project,
 * and (by default) a Client Portal account — plus the Admin-only follow-up
 * actions on that portal account (enable/disable, reset/regenerate password,
 * resend welcome).
 *
 * Every DB write (Client, Project, Client Contact, Lead, User) happens
 * inside a transaction; email is always sent *outside* the transaction and
 * never rolls it back — a delivery failure only ever falls back to
 * returning the plaintext password once in the response, per spec.
 */
class ClientOnboardingService
{
    public function __construct(private readonly ProjectService $projects) {}

    public function convertLead(Lead $lead, array $options): array
    {
        if ($lead->converted_client_id) {
            throw ValidationException::withMessages([
                'lead' => ['This lead has already been converted to a client.'],
            ]);
        }

        // A lead submitted through the "Start New Project" client-portal
        // form carries the same email as the submitter's existing portal
        // account — converting it should add a Project to that existing
        // Client, not attempt (and fail) to create a duplicate one.
        $existingClientUser = User::where('email', $lead->email)->where('role', UserRole::Client)->first();
        if ($existingClientUser) {
            return $this->convertLeadForExistingClient($lead, $existingClientUser->client);
        }

        if (User::where('email', $lead->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['A user with this email already exists.'],
            ]);
        }

        $createPortalAccount = $options['create_portal_account'] ?? true;

        [$client, $user, $password, $project] = DB::transaction(function () use ($lead, $createPortalAccount) {
            $client = Client::create([
                'name' => $lead->business_name,
                'industry' => $lead->industry,
                'city' => $lead->city,
                'account_manager_id' => $lead->assigned_to,
                'status' => ClientStatus::Active,
                'last_interaction_at' => now(),
            ]);

            // A primary contact is required by the Document "send" and
            // Meeting "invite" flows already built in earlier phases —
            // seed it from the lead's own contact info so those work
            // immediately for a freshly-converted client.
            $contact = $client->contacts()->create([
                'name' => $lead->full_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'is_primary' => true,
            ]);

            $lead->status = LeadStatus::Won;
            $lead->converted_client_id = $client->id;
            $lead->stage_entered_at = now();
            $lead->save();

            $lead->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Converted to client: {$client->name}",
            ]);
            $client->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Created from lead #{$lead->id}",
            ]);

            // Winning the deal (this conversion) is the one moment a real
            // Client exists to attach a Project to — so the lead becoming a
            // project happens automatically right here, not as a manual
            // follow-up step the team has to remember.
            $project = $this->projects->create([
                'client_id' => $client->id,
                'lead_id' => $lead->id,
                'name' => $lead->business_name,
                'description' => $this->projectDescription($lead),
                'budget' => $this->estimatedBudget($lead),
            ]);
            $this->copyAttachmentsToProject($lead, $project);

            $user = null;
            $password = null;

            if ($createPortalAccount) {
                [$user, $password] = $this->createPortalUser($client, $contact->name, $contact->email);
            }

            return [$client, $user, $password, $project];
        });

        $emailSent = null;
        if ($user) {
            $emailSent = ($options['send_welcome_email'] ?? true)
                ? $this->sendWelcomeEmail($user, $password, $client)
                : false;
        }

        return [
            'client' => $client->fresh(['contacts']),
            'user' => $user,
            'project' => $project,
            'temporary_password' => ($user && ! $emailSent) ? $password : null,
            'email_sent' => $emailSent,
            'existing_client' => false,
        ];
    }

    /** Converting a lead from an already-onboarded client just adds a Project — the Client and portal account already exist. */
    private function convertLeadForExistingClient(Lead $lead, Client $client): array
    {
        $project = DB::transaction(function () use ($lead, $client) {
            $lead->status = LeadStatus::Won;
            $lead->converted_client_id = $client->id;
            $lead->stage_entered_at = now();
            $lead->save();

            $lead->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Converted to a new project for existing client: {$client->name}",
            ]);

            $project = $this->projects->create([
                'client_id' => $client->id,
                'lead_id' => $lead->id,
                'name' => $lead->business_name,
                'description' => $this->projectDescription($lead),
                'budget' => $this->estimatedBudget($lead),
            ]);
            $this->copyAttachmentsToProject($lead, $project);

            $client->activities()->create([
                'user_id' => auth()->id(),
                'description' => "New project added from lead #{$lead->id}: {$project->name}",
            ]);

            return $project;
        });

        return [
            'client' => $client->fresh(['contacts']),
            'user' => null,
            'project' => $project,
            'temporary_password' => null,
            'email_sent' => null,
            'existing_client' => true,
        ];
    }

    public function enablePortal(Client $client): array
    {
        $user = $client->portalUsers()->first();

        if ($user) {
            $user->portal_enabled = true;
            $user->save();

            $client->activities()->create(['user_id' => auth()->id(), 'description' => 'Portal enabled']);

            return ['user' => $user, 'temporary_password' => null, 'email_sent' => null];
        }

        // No portal account exists yet for this client — create one now
        // from their primary contact, same as a normal conversion would.
        $contact = $client->contacts()->where('is_primary', true)->first() ?? $client->contacts()->first();

        if (! $contact || ! $contact->email) {
            throw ValidationException::withMessages([
                'client' => ['This client has no contact email on file — add a contact before enabling the portal.'],
            ]);
        }

        if (User::where('email', $contact->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['A user with this email already exists.'],
            ]);
        }

        [$user, $password] = DB::transaction(fn () => $this->createPortalUser($client, $contact->name, $contact->email));

        $emailSent = $this->sendWelcomeEmail($user, $password, $client);

        return [
            'user' => $user,
            'temporary_password' => $emailSent ? null : $password,
            'email_sent' => $emailSent,
        ];
    }

    public function disablePortal(Client $client): User
    {
        $user = $this->requirePortalUser($client);

        $user->portal_enabled = false;
        $user->save();

        $client->activities()->create(['user_id' => auth()->id(), 'description' => 'Portal disabled']);

        return $user;
    }

    public function resetPassword(Client $client): array
    {
        $user = $this->requirePortalUser($client);
        $password = $this->setNewPassword($user);

        $client->activities()->create(['user_id' => auth()->id(), 'description' => 'Password reset']);

        $emailSent = $this->sendWelcomeEmail($user, $password, $client);

        return [
            'user' => $user,
            'temporary_password' => $emailSent ? null : $password,
            'email_sent' => $emailSent,
        ];
    }

    /** Same as resetPassword() but never auto-emails — for handing the credential to the client directly. */
    public function regeneratePassword(Client $client): array
    {
        $user = $this->requirePortalUser($client);
        $password = $this->setNewPassword($user);

        $client->activities()->create(['user_id' => auth()->id(), 'description' => 'Password reset']);

        return ['user' => $user, 'temporary_password' => $password, 'email_sent' => false];
    }

    public function resendWelcomeEmail(Client $client): array
    {
        $user = $this->requirePortalUser($client);

        // The original plaintext password was never stored — a fresh one
        // must be generated to send anything usable.
        $password = $this->setNewPassword($user);

        $emailSent = $this->sendWelcomeEmail($user, $password, $client);

        return [
            'user' => $user,
            'temporary_password' => $emailSent ? null : $password,
            'email_sent' => $emailSent,
        ];
    }

    public function recordLogin(User $user): void
    {
        if ($user->role !== UserRole::Client) {
            return;
        }

        $isFirstLogin = $user->last_login_at === null;

        $user->last_login_at = now();
        $user->saveQuietly();

        if ($isFirstLogin && $user->client) {
            $user->client->activities()->create([
                'user_id' => $user->id,
                'description' => 'Client first login',
            ]);
        }
    }

    /** Files attached at intake (logo, brand guidelines, etc.) live on the Lead — copy them onto the Project too, since that's the only place a client or team member sees attachments after conversion. */
    private function copyAttachmentsToProject(Lead $lead, Project $project): void
    {
        foreach ($lead->attachments as $attachment) {
            $project->attachments()->create([
                'uploaded_by' => $attachment->uploaded_by,
                'uploaded_by_role' => $attachment->uploaded_by_role,
                'name' => $attachment->name,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'cloudinary_public_id' => $attachment->cloudinary_public_id,
                'url' => $attachment->url,
                'secure_url' => $attachment->secure_url,
            ]);
        }
    }

    /** Prefers the AI-generated summary (richest option) over the lead's own free-text intake fields. */
    private function projectDescription(Lead $lead): ?string
    {
        return $lead->ai_analysis['project_summary'] ?? $lead->project_idea ?: $lead->main_goal;
    }

    /** Midpoint of the AI-generated quotation range, if one was ever run — otherwise unset (0), same as a manually created project. */
    private function estimatedBudget(Lead $lead): float
    {
        $quotation = $lead->ai_analysis['quotation'] ?? null;

        return $quotation ? round((($quotation['range_low'] ?? 0) + ($quotation['range_high'] ?? 0)) / 2, 2) : 0;
    }

    private function requirePortalUser(Client $client): User
    {
        $user = $client->portalUsers()->first();
        abort_if(! $user, 404, 'This client has no portal account.');

        return $user;
    }

    /** @return array{0: User, 1: string} */
    private function createPortalUser(Client $client, string $name, string $email): array
    {
        $password = Str::password(12);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Client,
            'client_id' => $client->id,
            'must_change_password' => true,
            'portal_enabled' => true,
        ]);

        $client->activities()->create(['user_id' => auth()->id(), 'description' => 'Portal account created']);

        return [$user, $password];
    }

    private function setNewPassword(User $user): string
    {
        $password = Str::password(12);

        $user->password = Hash::make($password);
        $user->must_change_password = true;
        $user->save();

        return $password;
    }

    private function sendWelcomeEmail(User $user, string $password, Client $client): bool
    {
        try {
            Mail::to($user->email)->send(new ClientWelcomeMail($user, $password, $client));

            $client->activities()->create(['user_id' => auth()->id(), 'description' => 'Welcome email sent']);

            return true;
        } catch (Throwable $e) {
            Log::warning("Could not email client welcome message to {$user->email}: {$e->getMessage()}");

            $client->activities()->create(['user_id' => auth()->id(), 'description' => 'Welcome email failed']);

            return false;
        }
    }
}

<?php

namespace Tests\Feature;

use App\Enums\MeetingStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MeetingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_marking_unavailable_notifies_the_organizer(): void
    {
        Mail::fake();

        $organizer = User::factory()->create(['role' => UserRole::Admin]);
        $client = Client::factory()->create();
        $portalUser = User::factory()->create(['role' => UserRole::Client, 'client_id' => $client->id]);

        $meeting = Meeting::factory()->create([
            'status' => MeetingStatus::Upcoming,
            'client_id' => $client->id,
            'organizer_id' => $organizer->id,
        ]);

        $response = $this->actingAs($portalUser)
            ->postJson("/api/portal/meetings/{$meeting->id}/respond", [
                'response' => 'unavailable',
                'note' => 'Can we move to Thursday?',
            ]);

        $response->assertOk();

        $meeting->refresh();
        $this->assertSame('unavailable', $meeting->client_response->value);
        $this->assertSame('Can we move to Thursday?', $meeting->client_response_note);

        Mail::assertSent(\App\Mail\MeetingClientUnavailableMail::class, fn ($mail) => $mail->hasTo($organizer->email));
    }

    public function test_client_confirming_available_does_not_send_mail(): void
    {
        Mail::fake();

        $organizer = User::factory()->create(['role' => UserRole::Admin]);
        $client = Client::factory()->create();
        $portalUser = User::factory()->create(['role' => UserRole::Client, 'client_id' => $client->id]);

        $meeting = Meeting::factory()->create([
            'status' => MeetingStatus::Upcoming,
            'client_id' => $client->id,
            'organizer_id' => $organizer->id,
        ]);

        $this->actingAs($portalUser)
            ->postJson("/api/portal/meetings/{$meeting->id}/respond", ['response' => 'available'])
            ->assertOk();

        Mail::assertNothingSent();
    }

    public function test_client_cannot_respond_to_another_clients_meeting(): void
    {
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $portalUser = User::factory()->create(['role' => UserRole::Client, 'client_id' => $otherClient->id]);

        $meeting = Meeting::factory()->create(['status' => MeetingStatus::Upcoming, 'client_id' => $client->id]);

        $this->actingAs($portalUser)
            ->postJson("/api/portal/meetings/{$meeting->id}/respond", ['response' => 'available'])
            ->assertNotFound();
    }
}

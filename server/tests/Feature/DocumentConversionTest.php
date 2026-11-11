<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_contract_converts_to_invoice_with_same_line_items(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $contract = Document::factory()->contract()->create([
            'status' => DocumentStatus::Signed,
            'currency' => 'ETB',
            'tax_rate' => 10,
        ]);
        $contract->lineItems()->create([
            'description' => 'Website build',
            'quantity' => 1,
            'unit_price' => 50000,
            'tax_rate' => 10,
            'discount' => 0,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/documents/{$contract->id}/convert-to-invoice");

        $response->assertCreated();

        $invoice = Document::where('type', DocumentType::Invoice)->firstOrFail();
        $this->assertSame($contract->client_id, $invoice->client_id);
        $this->assertSame(1, $invoice->lineItems->count());
        $this->assertEquals(50000, $invoice->lineItems->first()->unit_price);
        $this->assertEquals(55000.0, (float) $invoice->total);
    }

    public function test_unsigned_contract_cannot_convert_to_invoice(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $contract = Document::factory()->contract()->create(['status' => DocumentStatus::Draft]);

        $this->actingAs($admin)
            ->postJson("/api/documents/{$contract->id}/convert-to-invoice")
            ->assertUnprocessable();
    }
}

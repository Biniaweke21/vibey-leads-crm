<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => DocumentType::Proposal,
            'client_id' => Client::factory(),
            'project_id' => null,
            'title' => fake()->randomElement(['Website Build', 'Mobile App', 'Brand Refresh', 'E-commerce Platform']).' '.fake()->randomElement(['Proposal', 'Agreement', 'Invoice']),
            'status' => DocumentStatus::Draft,
            'version' => 1,
            'currency' => 'ETB',
            'issue_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'tax_rate' => 0,
            'body' => [],
        ];
    }

    public function proposal(): static
    {
        return $this->state(fn () => [
            'type' => DocumentType::Proposal,
            'status' => fake()->randomElement([DocumentStatus::Draft, DocumentStatus::Sent, DocumentStatus::Approved]),
            'body' => [
                'executive_summary' => fake()->paragraph(),
                'scope_of_work' => fake()->paragraph(),
                'deliverables' => fake()->words(4),
                'timeline_phases' => [],
                'terms' => 'Payment in two installments: 50% deposit, 50% on delivery.',
            ],
        ]);
    }

    public function contract(): static
    {
        return $this->state(fn () => [
            'type' => DocumentType::Contract,
            'status' => fake()->randomElement([DocumentStatus::Draft, DocumentStatus::Sent, DocumentStatus::Signed]),
            'number' => 'CTR-'.fake()->unique()->numerify('####'),
            'signature_status' => 'unsigned',
            'body' => [
                'contract_terms' => fake()->paragraph(),
                'deliverables' => fake()->words(4),
            ],
        ]);
    }

    public function invoice(): static
    {
        return $this->state(fn () => [
            'type' => DocumentType::Invoice,
            'status' => fake()->randomElement([DocumentStatus::Sent, DocumentStatus::Paid, DocumentStatus::Overdue]),
            'number' => 'INV-'.fake()->unique()->numerify('####'),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'tax_rate' => 15,
        ]);
    }
}

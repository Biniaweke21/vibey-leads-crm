<?php

namespace Database\Factories;

use App\Enums\AiAnalysisStatus;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        $services = fake()->randomElements(
            ['Website', 'Mobile App', 'ERP System', 'AI Solution', 'Branding', 'E-commerce', 'SEO', 'Custom Software'],
            fake()->numberBetween(1, 3),
        );

        return [
            'full_name' => fake()->name(),
            'business_name' => fake()->company(),
            'company_name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+251 9'.fake()->numerify('# ### ####'),
            'city' => fake()->randomElement(['Addis Ababa', 'Hawassa', 'Mekelle', 'Jimma', 'Gondar']),
            'preferred_contact' => fake()->randomElement(['WhatsApp', 'Call', 'Email']),
            'industry' => fake()->randomElement(['Food & Beverage', 'Retail', 'Technology', 'Travel & Tourism', 'Finance']),
            'business_description' => fake()->sentence(),
            'project_idea' => fake()->sentence(),
            'main_goal' => fake()->sentence(),
            'services' => $services,
            'design_style' => fake()->randomElement(['Minimal', 'Modern', 'Luxury', 'Corporate', 'Premium']),
            'reference_links' => [],
            'budget' => fake()->randomElement(['Under 30,000 ETB', '30,000 – 50,000 ETB', '50,000 – 100,000 ETB', '100,000 – 300,000 ETB', '300,000 ETB+']),
            'timeline' => fake()->randomElement(['ASAP (within 2 weeks)', '1 Month', '2 Months', 'Flexible / No Rush']),
            'status' => fake()->randomElement(LeadStatus::cases()),
            'priority' => fake()->randomElement(LeadPriority::cases()),
            'ai_analysis_status' => fake()->randomElement(AiAnalysisStatus::cases()),
            'stage_entered_at' => fake()->dateTimeBetween('-20 days', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+14 days'),
        ];
    }

    public function status(LeadStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}

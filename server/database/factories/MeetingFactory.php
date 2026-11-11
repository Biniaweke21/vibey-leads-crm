<?php

namespace Database\Factories;

use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    /** title => matching type, kept together so seed data reads coherently. */
    private const TITLES = [
        'Discovery call' => MeetingType::DiscoveryCall,
        'Kickoff call' => MeetingType::Kickoff,
        'Monthly strategy sync' => MeetingType::StrategySync,
        'Review call' => MeetingType::Review,
        'Retainer check-in' => MeetingType::RetainerCheckin,
    ];

    public function definition(): array
    {
        $upcoming = fake()->boolean();
        [$title, $type] = fake()->randomElement(
            array_map(fn ($t, $v) => [$t, $v], array_keys(self::TITLES), self::TITLES),
        );

        $startsAt = $upcoming
            ? fake()->dateTimeBetween('now', '+14 days')
            : fake()->dateTimeBetween('-30 days', '-1 day');
        $durationMinutes = fake()->randomElement([30, 45, 60]);

        return [
            'title' => $title,
            'type' => $type,
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify("+{$durationMinutes} minutes"),
            'location' => fake()->randomElement(['Google Meet', 'Phone', 'Office']),
            'status' => $upcoming ? MeetingStatus::Upcoming : MeetingStatus::Completed,
        ];
    }
}

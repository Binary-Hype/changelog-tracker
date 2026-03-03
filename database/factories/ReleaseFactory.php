<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Release>
 */
class ReleaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'github_id' => fake()->unique()->randomNumber(8),
            'tag_name' => 'v'.fake()->semver(),
            'name' => 'Release '.fake()->semver(),
            'body' => fake()->paragraphs(3, true),
            'html_url' => fake()->url(),
            'published_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'notified_at' => null,
            'notification_attempts' => 0,
        ];
    }

    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => now(),
            'notification_attempts' => 1,
        ]);
    }

    public function failedNotification(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => null,
            'notification_attempts' => 1,
        ]);
    }

    public function exhaustedRetries(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => null,
            'notification_attempts' => config('changelog-tracker.notifications.max_attempts'),
        ]);
    }
}

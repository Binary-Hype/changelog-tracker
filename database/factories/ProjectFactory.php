<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'owner' => fake()->userName(),
            'repo' => fake()->slug(2),
            'description' => fake()->sentence(),
            'is_active' => true,
            'check_interval_minutes' => 30,
            'last_checked_at' => null,
            'include_prereleases' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withPrereleases(): static
    {
        return $this->state(fn (array $attributes) => [
            'include_prereleases' => true,
        ]);
    }

    public function recentlyChecked(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_checked_at' => now()->subMinutes(5),
        ]);
    }

    public function dueForCheck(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_checked_at' => now()->subHours(2),
        ]);
    }
}

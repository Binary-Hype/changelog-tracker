<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlackChannel>
 */
class SlackChannelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => '#'.fake()->slug(2),
            'webhook_url' => 'https://hooks.slack.com/services/'.fake()->regexify('[A-Z0-9]{9}/[A-Z0-9]{11}/[a-zA-Z0-9]{24}'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

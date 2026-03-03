<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\SlackChannel;
use Illuminate\Database\Seeder;

class SlackChannelSeeder extends Seeder
{
    public function run(): void
    {
        $channel = SlackChannel::factory()->create([
            'name' => '#releases',
            'webhook_url' => 'https://hooks.slack.com/services/EXAMPLE/WEBHOOK/URL',
        ]);

        Project::all()->each(function (Project $project) use ($channel): void {
            $project->slackChannels()->attach($channel);
        });
    }
}

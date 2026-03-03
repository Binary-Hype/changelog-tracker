<?php

use App\Models\Project;
use App\Models\SlackChannel;

test('slack channel belongs to many projects', function () {
    $channel = SlackChannel::factory()->create();
    $projects = Project::factory()->count(2)->create();
    $channel->projects()->attach($projects);

    expect($channel->projects)->toHaveCount(2);
});

test('slack channel encrypts webhook url', function () {
    $channel = SlackChannel::factory()->create([
        'webhook_url' => 'https://hooks.slack.com/services/TEST/HOOK/URL',
    ]);

    $channel->refresh();

    expect($channel->webhook_url)->toBe('https://hooks.slack.com/services/TEST/HOOK/URL');

    $rawValue = \Illuminate\Support\Facades\DB::table('slack_channels')
        ->where('id', $channel->id)
        ->value('webhook_url');

    expect($rawValue)->not->toBe('https://hooks.slack.com/services/TEST/HOOK/URL');
});

test('slack channel casts is_active as boolean', function () {
    $channel = SlackChannel::factory()->create(['is_active' => true]);

    expect($channel->is_active)->toBeBool()->toBeTrue();
});

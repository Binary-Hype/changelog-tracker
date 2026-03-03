<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\SlackChannel;
use Illuminate\Support\Facades\Http;

test('retries failed notifications', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $project = Project::factory()->create();
    $channel = SlackChannel::factory()->create();
    $project->slackChannels()->attach($channel);

    Release::factory()->failedNotification()->for($project)->create();

    $this->artisan('app:retry-notifications')
        ->assertSuccessful();

    Http::assertSentCount(1);
});

test('does not retry already notified releases', function () {
    Http::fake();

    Release::factory()->notified()->create();

    $this->artisan('app:retry-notifications')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('does not retry exhausted releases', function () {
    Http::fake();

    Release::factory()->exhaustedRetries()->create();

    $this->artisan('app:retry-notifications')
        ->assertSuccessful();

    Http::assertNothingSent();
});

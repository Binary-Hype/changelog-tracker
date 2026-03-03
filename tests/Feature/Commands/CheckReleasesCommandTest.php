<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\SlackChannel;
use Illuminate\Support\Facades\Http;

test('checks projects due for release updates', function () {
    Http::fake([
        'api.github.com/repos/laravel/framework/releases*' => Http::response([
            [
                'id' => 99999,
                'tag_name' => 'v12.0.0',
                'name' => 'Laravel 12',
                'body' => 'New release',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v12.0.0',
                'published_at' => '2025-01-01T00:00:00Z',
                'prerelease' => false,
            ],
        ]),
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $project = Project::factory()->dueForCheck()->create([
        'owner' => 'laravel',
        'repo' => 'framework',
    ]);

    $channel = SlackChannel::factory()->create();
    $project->slackChannels()->attach($channel);

    $this->artisan('app:check-releases')
        ->assertSuccessful();

    expect(Release::count())->toBe(1)
        ->and(Release::first()->tag_name)->toBe('v12.0.0')
        ->and($project->fresh()->last_checked_at)->not->toBeNull();
});

test('skips projects not due for check', function () {
    Http::fake();

    Project::factory()->recentlyChecked()->create();

    $this->artisan('app:check-releases')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('skips inactive projects', function () {
    Http::fake();

    Project::factory()->inactive()->create();

    $this->artisan('app:check-releases')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('does not duplicate existing releases', function () {
    Http::fake([
        'api.github.com/repos/*/releases*' => Http::response([
            [
                'id' => 12345,
                'tag_name' => 'v1.0.0',
                'name' => 'Release 1',
                'body' => 'Notes',
                'html_url' => 'https://github.com/test/repo/releases/tag/v1.0.0',
                'published_at' => '2025-01-01T00:00:00Z',
                'prerelease' => false,
            ],
        ]),
    ]);

    $project = Project::factory()->dueForCheck()->create();
    Release::factory()->for($project)->create(['github_id' => 12345]);

    $this->artisan('app:check-releases')
        ->assertSuccessful();

    expect(Release::count())->toBe(1);
});

<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\SlackChannel;

test('project has many releases', function () {
    $project = Project::factory()->create();
    Release::factory()->count(3)->for($project)->create();

    expect($project->releases)->toHaveCount(3);
});

test('project belongs to many slack channels', function () {
    $project = Project::factory()->create();
    $channels = SlackChannel::factory()->count(2)->create();
    $project->slackChannels()->attach($channels);

    expect($project->slackChannels)->toHaveCount(2);
});

test('project casts attributes correctly', function () {
    $project = Project::factory()->create([
        'is_active' => true,
        'include_prereleases' => false,
        'last_checked_at' => now(),
    ]);

    expect($project->is_active)->toBeBool()
        ->and($project->include_prereleases)->toBeBool()
        ->and($project->last_checked_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('project github url accessor', function () {
    $project = Project::factory()->create([
        'owner' => 'laravel',
        'repo' => 'framework',
    ]);

    expect($project->github_url)->toBe('https://github.com/laravel/framework');
});

test('project is due for check when never checked', function () {
    $project = Project::factory()->create([
        'is_active' => true,
        'last_checked_at' => null,
    ]);

    expect($project->isDueForCheck())->toBeTrue();
});

test('project is not due for check when recently checked', function () {
    $project = Project::factory()->recentlyChecked()->create();

    expect($project->isDueForCheck())->toBeFalse();
});

test('project is due for check when check interval elapsed', function () {
    $project = Project::factory()->dueForCheck()->create();

    expect($project->isDueForCheck())->toBeTrue();
});

test('inactive project is never due for check', function () {
    $project = Project::factory()->inactive()->create([
        'last_checked_at' => null,
    ]);

    expect($project->isDueForCheck())->toBeFalse();
});

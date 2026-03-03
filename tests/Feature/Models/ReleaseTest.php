<?php

use App\Models\Project;
use App\Models\Release;

test('release belongs to project', function () {
    $release = Release::factory()->create();

    expect($release->project)->toBeInstanceOf(Project::class);
});

test('release casts attributes correctly', function () {
    $release = Release::factory()->create([
        'published_at' => now(),
        'notified_at' => now(),
        'github_id' => 12345,
    ]);

    expect($release->published_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($release->notified_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($release->github_id)->toBeInt();
});

test('release is notified when notified_at is set', function () {
    $release = Release::factory()->notified()->create();

    expect($release->isNotified())->toBeTrue();
});

test('release is pending notification when not notified and no attempts', function () {
    $release = Release::factory()->create([
        'notified_at' => null,
        'notification_attempts' => 0,
    ]);

    expect($release->isPendingNotification())->toBeTrue();
});

test('release is retryable when under max attempts and within window', function () {
    $release = Release::factory()->failedNotification()->create();

    expect($release->isRetryable())->toBeTrue();
});

test('release is not retryable when max attempts exhausted', function () {
    $release = Release::factory()->exhaustedRetries()->create();

    expect($release->isRetryable())->toBeFalse();
});

test('release is not retryable when already notified', function () {
    $release = Release::factory()->notified()->create();

    expect($release->isRetryable())->toBeFalse();
});

<?php

use App\Models\Release;
use App\Models\SlackChannel;
use App\Services\SlackNotifier;
use Illuminate\Support\Facades\Http;

test('sends notification to slack channel', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $release = Release::factory()->create([
        'body' => '## Changes\n- Fixed bug\n- Added feature',
    ]);
    $channel = SlackChannel::factory()->create();

    $notifier = app(SlackNotifier::class);
    $result = $notifier->notify($release, $channel);

    expect($result)->toBeTrue();

    Http::assertSentCount(1);
});

test('returns false when slack api fails', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('invalid_payload', 400),
    ]);

    $release = Release::factory()->create();
    $channel = SlackChannel::factory()->create();

    $notifier = app(SlackNotifier::class);
    $result = $notifier->notify($release, $channel);

    expect($result)->toBeFalse();
});

test('truncates long release body', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $release = Release::factory()->create([
        'body' => str_repeat('a', 5000),
    ]);
    $channel = SlackChannel::factory()->create();

    $notifier = app(SlackNotifier::class);
    $notifier->notify($release, $channel);

    Http::assertSent(function ($request) {
        $blocks = $request->data()['blocks'];
        $bodyBlock = collect($blocks)->firstWhere('type', 'section');

        return mb_strlen($bodyBlock['text']['text']) <= 3000;
    });
});

test('sends test message', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
    ]);

    $channel = SlackChannel::factory()->create();

    $notifier = app(SlackNotifier::class);
    $result = $notifier->sendTestMessage($channel);

    expect($result)->toBeTrue();
    Http::assertSentCount(1);
});

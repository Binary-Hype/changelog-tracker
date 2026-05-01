<?php

use App\Contracts\ChangelogSummarizerContract;
use App\Models\Release;
use App\Models\SlackChannel;
use App\Services\SlackNotifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

function fakeSummarizer(?string $return = null, bool $throws = false): object
{
    return new class($return ?? 'TLDR sentence. **Breaking**: `Foo::bar()` removed.', $throws) implements ChangelogSummarizerContract
    {
        public int $calls = 0;

        public function __construct(public string $return, public bool $throws) {}

        public function summarize(string $body): string
        {
            $this->calls++;

            if ($this->throws) {
                throw new RuntimeException('boom');
            }

            return $this->return;
        }
    };
}

test('posts AI summary instead of raw body when summarizer succeeds', function () {
    Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);
    $fake = fakeSummarizer('AI lead. **New**: `--flag` added.');
    $this->app->instance(ChangelogSummarizerContract::class, $fake);

    $release = Release::factory()->create([
        'body' => '## Internal\n- raw release body content xyz',
        'summary' => null,
    ]);
    $channel = SlackChannel::factory()->create();

    app(SlackNotifier::class)->notify($release, $channel);

    Http::assertSent(function ($request) {
        $blocks = $request->data()['blocks'];
        $section = collect($blocks)->firstWhere('type', 'section');

        return str_contains($section['text']['text'], 'AI lead.')
            && ! str_contains($section['text']['text'], 'raw release body content xyz');
    });
});

test('falls back to raw body and logs a warning when summarizer throws', function () {
    Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);
    Log::spy();
    $fake = fakeSummarizer(throws: true);
    $this->app->instance(ChangelogSummarizerContract::class, $fake);

    $release = Release::factory()->create([
        'body' => 'unique-fallback-marker-zzz',
        'summary' => null,
    ]);
    $channel = SlackChannel::factory()->create();

    app(SlackNotifier::class)->notify($release, $channel);

    Http::assertSent(function ($request) {
        $blocks = $request->data()['blocks'];
        $section = collect($blocks)->firstWhere('type', 'section');

        return str_contains($section['text']['text'], 'unique-fallback-marker-zzz');
    });

    Log::shouldHaveReceived('warning')
        ->withArgs(fn ($msg) => $msg === 'Changelog summarization failed')
        ->once();
});

test('caches summary on the release row after first call', function () {
    Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);
    $fake = fakeSummarizer('cached-on-row');
    $this->app->instance(ChangelogSummarizerContract::class, $fake);

    $release = Release::factory()->create(['body' => 'some body', 'summary' => null]);
    $channel = SlackChannel::factory()->create();

    app(SlackNotifier::class)->notify($release, $channel);

    expect($release->fresh()->summary)->toBe('cached-on-row');
});

test('reuses cached summary without calling AI again', function () {
    Http::fake(['hooks.slack.com/*' => Http::response('ok', 200)]);
    $fake = fakeSummarizer('should-not-be-used');
    $this->app->instance(ChangelogSummarizerContract::class, $fake);

    $release = Release::factory()->create([
        'body' => 'original body',
        'summary' => 'pre-existing-summary',
    ]);
    $channelA = SlackChannel::factory()->create();
    $channelB = SlackChannel::factory()->create();

    app(SlackNotifier::class)->notify($release, $channelA);
    app(SlackNotifier::class)->notify($release, $channelB);

    expect($fake->calls)->toBe(0);

    Http::assertSentCount(2);
    Http::assertSent(function ($request) {
        $blocks = $request->data()['blocks'];
        $section = collect($blocks)->firstWhere('type', 'section');

        return str_contains($section['text']['text'], 'pre-existing-summary');
    });
});

<?php

namespace App\Services;

use App\Models\Release;
use App\Models\SlackChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotifier
{
    private const MAX_BODY_LENGTH = 3000;

    public function __construct(
        private MarkdownToSlackConverter $markdownConverter
    ) {}

    public function notify(Release $release, SlackChannel $channel): bool
    {
        $release->loadMissing('project');
        $project = $release->project;

        $body = $this->markdownConverter->convert($release->body ?? '');
        if (mb_strlen($body) > self::MAX_BODY_LENGTH) {
            $body = mb_substr($body, 0, self::MAX_BODY_LENGTH - 3).'...';
        }

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => "{$project->name} {$release->tag_name}",
                    'emoji' => true,
                ],
            ],
        ];

        if ($body) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $body,
                ],
            ];
        }

        $blocks[] = [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => "Released on {$release->published_at?->format('M j, Y')} | {$project->owner}/{$project->repo}",
                ],
            ],
        ];

        $blocks[] = [
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => 'View on GitHub',
                        'emoji' => true,
                    ],
                    'url' => $release->html_url,
                    'action_id' => 'view_release',
                ],
            ],
        ];

        $payload = [
            'blocks' => $blocks,
            'text' => "New release: {$project->name} {$release->tag_name}",
        ];

        $response = Http::post($channel->webhook_url, $payload);

        if ($response->successful()) {
            return true;
        }

        Log::error('Slack notification failed', [
            'release_id' => $release->id,
            'channel' => $channel->name,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    public function sendTestMessage(SlackChannel $channel): bool
    {
        $payload = [
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => 'Changelog Tracker - Test Message',
                        'emoji' => true,
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => 'This is a test message from Changelog Tracker. If you see this, notifications are working correctly!',
                    ],
                ],
            ],
            'text' => 'Changelog Tracker - Test Message',
        ];

        $response = Http::post($channel->webhook_url, $payload);

        return $response->successful();
    }
}

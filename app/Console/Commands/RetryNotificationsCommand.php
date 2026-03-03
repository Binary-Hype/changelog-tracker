<?php

namespace App\Console\Commands;

use App\Models\Release;
use App\Services\SlackNotifier;
use Illuminate\Console\Command;

class RetryNotificationsCommand extends Command
{
    protected $signature = 'app:retry-notifications';

    protected $description = 'Retry failed Slack notifications for recent releases';

    public function handle(SlackNotifier $slackNotifier): int
    {
        $maxAttempts = config('changelog-tracker.notifications.max_attempts');
        $retryWindow = config('changelog-tracker.notifications.retry_window_hours');

        $releases = Release::query()
            ->whereNull('notified_at')
            ->where('notification_attempts', '>', 0)
            ->where('notification_attempts', '<', $maxAttempts)
            ->where('created_at', '>', now()->subHours($retryWindow))
            ->with(['project.slackChannels'])
            ->get();

        if ($releases->isEmpty()) {
            $this->info('No notifications to retry.');

            return self::SUCCESS;
        }

        $this->info("Retrying notifications for {$releases->count()} release(s)...");

        foreach ($releases as $release) {
            $activeChannels = $release->project->slackChannels->where('is_active', true);

            foreach ($activeChannels as $channel) {
                $success = $slackNotifier->notify($release, $channel);

                $release->increment('notification_attempts');

                if ($success) {
                    $release->update(['notified_at' => now()]);
                    $this->info("  Notified {$channel->name} about {$release->project->name} {$release->tag_name}");
                } else {
                    $this->warn("  Retry failed for {$channel->name} about {$release->project->name} {$release->tag_name}");
                }
            }
        }

        return self::SUCCESS;
    }
}

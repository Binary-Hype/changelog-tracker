<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Release;
use App\Services\GitHubService;
use App\Services\SlackNotifier;
use Illuminate\Console\Command;

class CheckReleasesCommand extends Command
{
    protected $signature = 'app:check-releases';

    protected $description = 'Check GitHub repositories for new releases and notify Slack channels';

    public function handle(GitHubService $github, SlackNotifier $slackNotifier): int
    {
        $projects = Project::query()
            ->where('is_active', true)
            ->with('slackChannels')
            ->get()
            ->filter(fn (Project $project) => $project->isDueForCheck());

        if ($projects->isEmpty()) {
            $this->info('No projects due for checking.');

            return self::SUCCESS;
        }

        $this->info("Checking {$projects->count()} project(s) for new releases...");

        foreach ($projects as $project) {
            try {
                $this->checkProject($project, $github, $slackNotifier);
            } catch (\Throwable $e) {
                $this->error("Error checking {$project->owner}/{$project->repo}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    private function checkProject(Project $project, GitHubService $github, SlackNotifier $slackNotifier): void
    {
        $this->line("Checking {$project->owner}/{$project->repo}...");

        $releases = $github->fetchReleases(
            $project->owner,
            $project->repo,
            $project->include_prereleases,
        );

        $newReleasesCount = 0;

        foreach ($releases as $releaseData) {
            $existingRelease = Release::query()
                ->where('github_id', $releaseData['id'])
                ->exists();

            if ($existingRelease) {
                continue;
            }

            $release = Release::create([
                'project_id' => $project->id,
                'github_id' => $releaseData['id'],
                'tag_name' => $releaseData['tag_name'],
                'name' => $releaseData['name'] ?? null,
                'body' => $releaseData['body'] ?? null,
                'html_url' => $releaseData['html_url'],
                'published_at' => $releaseData['published_at'] ?? null,
            ]);

            $newReleasesCount++;

            $this->notifyChannels($release, $project, $slackNotifier);
        }

        $project->update(['last_checked_at' => now()]);

        if ($newReleasesCount > 0) {
            $this->info("  Found {$newReleasesCount} new release(s).");
        } else {
            $this->line('  No new releases.');
        }
    }

    private function notifyChannels(Release $release, Project $project, SlackNotifier $slackNotifier): void
    {
        $activeChannels = $project->slackChannels->where('is_active', true);

        foreach ($activeChannels as $channel) {
            $success = $slackNotifier->notify($release, $channel);

            $release->increment('notification_attempts');

            if ($success) {
                $release->update(['notified_at' => now()]);
                $this->line("  Notified {$channel->name} about {$release->tag_name}");
            } else {
                $this->warn("  Failed to notify {$channel->name} about {$release->tag_name}");
            }
        }
    }
}

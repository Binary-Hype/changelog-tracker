<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Services\GitHubService;
use App\Services\SlackNotifier;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('checkNow')
                ->label('Check Now')
                ->icon('heroicon-o-arrow-path')
                ->action(function (): void {
                    $project = $this->getRecord();
                    $github = app(GitHubService::class);
                    $slackNotifier = app(SlackNotifier::class);

                    $releases = $github->fetchReleases(
                        $project->owner,
                        $project->repo,
                        $project->include_prereleases,
                    );

                    $newCount = 0;
                    foreach ($releases as $releaseData) {
                        $exists = $project->releases()
                            ->where('github_id', $releaseData['id'])
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        $release = $project->releases()->create([
                            'github_id' => $releaseData['id'],
                            'tag_name' => $releaseData['tag_name'],
                            'name' => $releaseData['name'] ?? null,
                            'body' => $releaseData['body'] ?? null,
                            'html_url' => $releaseData['html_url'],
                            'published_at' => $releaseData['published_at'] ?? null,
                        ]);

                        $newCount++;

                        foreach ($project->slackChannels->where('is_active', true) as $channel) {
                            $success = $slackNotifier->notify($release, $channel);
                            $release->increment('notification_attempts');
                            if ($success) {
                                $release->update(['notified_at' => now()]);
                            }
                        }
                    }

                    $project->update(['last_checked_at' => now()]);

                    Notification::make()
                        ->title($newCount > 0 ? "Found {$newCount} new release(s)" : 'No new releases found')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}

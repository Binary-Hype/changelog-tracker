<?php

namespace App\Console\Commands;

use App\Contracts\ChangelogSummarizerContract;
use App\Models\Release;
use Illuminate\Console\Command;
use Throwable;

class SummarizeReleaseCommand extends Command
{
    protected $signature = 'release:summarize {release : The release ID} {--save : Persist the summary to the releases table}';

    protected $description = 'Generate an AI summary for a release without posting to Slack';

    public function handle(ChangelogSummarizerContract $summarizer): int
    {
        $release = Release::find($this->argument('release'));

        if (! $release) {
            $this->error("Release {$this->argument('release')} not found.");

            return self::FAILURE;
        }

        $body = trim((string) $release->body);

        if ($body === '') {
            $this->warn('Release has no body to summarize.');

            return self::SUCCESS;
        }

        $this->info("Summarizing {$release->tag_name} ({$release->id})...");

        try {
            $summary = $summarizer->summarize($body);
        } catch (Throwable $e) {
            $this->error("Summarization failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->newLine();
        $this->line($summary);
        $this->newLine();
        $this->line(sprintf('— %d characters', mb_strlen($summary)));

        if ($this->option('save')) {
            $release->forceFill(['summary' => $summary])->save();
            $this->info('Saved to releases.summary.');
        }

        return self::SUCCESS;
    }
}

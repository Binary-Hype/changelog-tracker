<?php

namespace App\Providers;

use App\Ai\Agents\ChangelogSummarizer;
use App\Contracts\ChangelogSummarizerContract;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ChangelogSummarizerContract::class, ChangelogSummarizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

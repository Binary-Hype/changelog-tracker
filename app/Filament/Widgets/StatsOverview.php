<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Release;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active Projects', Project::query()->where('is_active', true)->count())
                ->icon('heroicon-o-folder'),
            Stat::make('Releases This Week', Release::query()->where('published_at', '>=', now()->subWeek())->count())
                ->icon('heroicon-o-tag'),
            Stat::make('Pending Notifications', Release::query()->whereNull('notified_at')->where('notification_attempts', 0)->count())
                ->icon('heroicon-o-bell'),
        ];
    }
}

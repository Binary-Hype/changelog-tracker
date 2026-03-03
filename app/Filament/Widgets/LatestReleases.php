<?php

namespace App\Filament\Widgets;

use App\Models\Release;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestReleases extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Release::query()
                    ->with('project')
                    ->latest('published_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tag_name')
                    ->label('Tag'),
                Tables\Columns\TextColumn::make('published_at')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notification_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (Release $record): string {
                        if ($record->isNotified()) {
                            return 'Notified';
                        }
                        if ($record->notification_attempts > 0) {
                            return 'Failed';
                        }

                        return 'Pending';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Notified' => 'success',
                        'Failed' => 'warning',
                        'Pending' => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}

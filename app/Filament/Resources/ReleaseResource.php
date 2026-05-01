<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReleaseResource\Pages\ListReleases;
use App\Filament\Resources\ReleaseResource\Pages\ViewRelease;
use App\Models\Release;
use App\Services\SlackNotifier;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReleaseResource extends Resource
{
    protected static ?string $model = Release::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Release Details')
                    ->schema([
                        TextEntry::make('project.name')
                            ->label('Project'),
                        TextEntry::make('tag_name')
                            ->label('Tag'),
                        TextEntry::make('name')
                            ->label('Release Name'),
                        TextEntry::make('published_at')
                            ->dateTime(),
                        TextEntry::make('html_url')
                            ->label('GitHub URL')
                            ->url(fn (Release $record): string => $record->html_url)
                            ->openUrlInNewTab(),
                        TextEntry::make('notified_at')
                            ->dateTime()
                            ->placeholder('Not notified'),
                    ])
                    ->columns(2),
                Section::make('Changelog')
                    ->schema([
                        TextEntry::make('body')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('project.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tag_name')
                    ->label('Tag')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('notification_status')
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
            ->filters([
                SelectFilter::make('project')
                    ->relationship('project', 'name'),
                SelectFilter::make('status')
                    ->options([
                        'notified' => 'Notified',
                        'failed' => 'Failed',
                        'pending' => 'Pending',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value']) {
                            'notified' => $query->whereNotNull('notified_at'),
                            'failed' => $query->whereNull('notified_at')->where('notification_attempts', '>', 0),
                            'pending' => $query->whereNull('notified_at')->where('notification_attempts', 0),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('renotify')
                    ->label('Re-notify')
                    ->icon('heroicon-o-bell')
                    ->requiresConfirmation()
                    ->action(function (Release $record): void {
                        $record->update([
                            'notified_at' => null,
                            'notification_attempts' => 0,
                        ]);

                        $slackNotifier = app(SlackNotifier::class);

                        foreach ($record->project->slackChannels->where('is_active', true) as $channel) {
                            $success = $slackNotifier->notify($record, $channel);
                            $record->increment('notification_attempts');
                            if ($success) {
                                $record->update(['notified_at' => now()]);
                            }
                        }
                    }),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReleases::route('/'),
            'view' => ViewRelease::route('/{record}'),
        ];
    }
}

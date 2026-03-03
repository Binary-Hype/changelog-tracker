<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReleaseResource\Pages;
use App\Models\Release;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReleaseResource extends Resource
{
    protected static ?string $model = Release::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Release Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('project.name')
                            ->label('Project'),
                        Infolists\Components\TextEntry::make('tag_name')
                            ->label('Tag'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Release Name'),
                        Infolists\Components\TextEntry::make('published_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('html_url')
                            ->label('GitHub URL')
                            ->url(fn (Release $record): string => $record->html_url)
                            ->openUrlInNewTab(),
                        Infolists\Components\TextEntry::make('notified_at')
                            ->dateTime()
                            ->placeholder('Not notified'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Changelog')
                    ->schema([
                        Infolists\Components\TextEntry::make('body')
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
                Tables\Columns\TextColumn::make('project.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tag_name')
                    ->label('Tag')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
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
            ->filters([
                Tables\Filters\SelectFilter::make('project')
                    ->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')
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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('renotify')
                    ->label('Re-notify')
                    ->icon('heroicon-o-bell')
                    ->requiresConfirmation()
                    ->action(function (Release $record): void {
                        $record->update([
                            'notified_at' => null,
                            'notification_attempts' => 0,
                        ]);

                        $slackNotifier = app(\App\Services\SlackNotifier::class);

                        foreach ($record->project->slackChannels->where('is_active', true) as $channel) {
                            $success = $slackNotifier->notify($record, $channel);
                            $record->increment('notification_attempts');
                            if ($success) {
                                $record->update(['notified_at' => now()]);
                            }
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReleases::route('/'),
            'view' => Pages\ViewRelease::route('/{record}'),
        ];
    }
}

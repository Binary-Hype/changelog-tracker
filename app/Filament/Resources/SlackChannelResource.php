<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlackChannelResource\Pages\CreateSlackChannel;
use App\Filament\Resources\SlackChannelResource\Pages\EditSlackChannel;
use App\Filament\Resources\SlackChannelResource\Pages\ListSlackChannels;
use App\Models\SlackChannel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SlackChannelResource extends Resource
{
    protected static ?string $model = SlackChannel::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('#channel-name'),
                TextInput::make('webhook_url')
                    ->label('Webhook URL')
                    ->required()
                    ->password()
                    ->revealable()
                    ->placeholder('https://hooks.slack.com/services/...')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('is_active'),
                TextColumn::make('projects_count')
                    ->counts('projects')
                    ->label('Projects')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSlackChannels::route('/'),
            'create' => CreateSlackChannel::route('/create'),
            'edit' => EditSlackChannel::route('/{record}/edit'),
        ];
    }
}

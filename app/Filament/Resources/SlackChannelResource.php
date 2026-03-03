<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlackChannelResource\Pages;
use App\Models\SlackChannel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SlackChannelResource extends Resource
{
    protected static ?string $model = SlackChannel::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('#channel-name'),
                Forms\Components\TextInput::make('webhook_url')
                    ->label('Webhook URL')
                    ->required()
                    ->password()
                    ->revealable()
                    ->placeholder('https://hooks.slack.com/services/...')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active'),
                Tables\Columns\TextColumn::make('projects_count')
                    ->counts('projects')
                    ->label('Projects')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListSlackChannels::route('/'),
            'create' => Pages\CreateSlackChannel::route('/create'),
            'edit' => Pages\EditSlackChannel::route('/{record}/edit'),
        ];
    }
}

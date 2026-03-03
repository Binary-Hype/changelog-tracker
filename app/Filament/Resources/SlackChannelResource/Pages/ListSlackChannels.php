<?php

namespace App\Filament\Resources\SlackChannelResource\Pages;

use App\Filament\Resources\SlackChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSlackChannels extends ListRecords
{
    protected static string $resource = SlackChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

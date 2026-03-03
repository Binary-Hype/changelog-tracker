<?php

namespace App\Filament\Resources\SlackChannelResource\Pages;

use App\Filament\Resources\SlackChannelResource;
use App\Services\SlackNotifier;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSlackChannel extends EditRecord
{
    protected static string $resource = SlackChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('testMessage')
                ->label('Send Test Message')
                ->icon('heroicon-o-paper-airplane')
                ->action(function (): void {
                    $slackNotifier = app(SlackNotifier::class);
                    $success = $slackNotifier->sendTestMessage($this->getRecord());

                    if ($success) {
                        Notification::make()
                            ->title('Test message sent successfully!')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Failed to send test message')
                            ->danger()
                            ->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }
}

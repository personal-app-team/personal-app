<?php

namespace App\Filament\Resources\WorkRequestResource\Pages;

use App\Filament\Resources\WorkRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkRequest extends CreateRecord
{
    protected static string $resource = WorkRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Заявка создана';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('close')
                ->label('Закрыть')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url($this->getResource()::getUrl('index'))
                ->extraAttributes(['class' => 'ml-auto']),
        ];
    }
}

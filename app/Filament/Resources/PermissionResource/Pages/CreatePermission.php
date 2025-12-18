<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Разрешение создано';
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

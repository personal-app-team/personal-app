<?php

namespace App\Filament\Resources\WorkRequestResource\Pages;

use App\Filament\Resources\WorkRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkRequest extends EditRecord
{
    protected static string $resource = WorkRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Заявка сохранена';
    }

    protected function getHeaderActions(): array
    {
        return [
            // Кнопка удаления (будет слева)
            Actions\DeleteAction::make()
                ->label('Удалить заявку'),
            
            // Кнопка закрытия (будет справа из-за ml-auto)
            Actions\Action::make('close')
                ->label('Закрыть')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url($this->getResource()::getUrl('index'))
                ->extraAttributes(['class' => 'ml-auto']),
        ];
    }
}

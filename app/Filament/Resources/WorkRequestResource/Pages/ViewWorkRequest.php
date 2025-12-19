<?php

namespace App\Filament\Resources\WorkRequestResource\Pages;

use App\Filament\Resources\WorkRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkRequest extends ViewRecord
{
    protected static string $resource = WorkRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Редактировать')
                ->visible(fn (): bool => 
                    $this->record->dispatcher_id === auth()->id() || 
                    auth()->user()->hasRole('admin')
                ),
        ];
    }
}

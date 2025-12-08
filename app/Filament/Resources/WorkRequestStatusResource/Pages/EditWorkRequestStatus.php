<?php

namespace App\Filament\Resources\WorkRequestStatusResource\Pages;

use App\Filament\Resources\WorkRequestStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkRequestStatus extends EditRecord
{
    protected static string $resource = WorkRequestStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\WorkRequestStatusResource\Pages;

use App\Filament\Resources\WorkRequestStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkRequestStatuses extends ListRecords
{
    protected static string $resource = WorkRequestStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

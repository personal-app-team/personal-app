<?php

namespace App\Filament\Resources\PositionChangeRequestResource\Pages;

use App\Filament\Resources\PositionChangeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPositionChangeRequests extends ListRecords
{
    protected static string $resource = PositionChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

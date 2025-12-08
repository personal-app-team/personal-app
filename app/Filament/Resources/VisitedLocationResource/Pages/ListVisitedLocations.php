<?php

namespace App\Filament\Resources\VisitedLocationResource\Pages;

use App\Filament\Resources\VisitedLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitedLocations extends ListRecords
{
    protected static string $resource = VisitedLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

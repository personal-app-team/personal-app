<?php

namespace App\Filament\Resources\VisitedLocationResource\Pages;

use App\Filament\Resources\VisitedLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVisitedLocation extends EditRecord
{
    protected static string $resource = VisitedLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

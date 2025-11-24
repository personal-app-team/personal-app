<?php

namespace App\Filament\Resources\TraineeRequestResource\Pages;

use App\Filament\Resources\TraineeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTraineeRequests extends ListRecords
{
    protected static string $resource = TraineeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

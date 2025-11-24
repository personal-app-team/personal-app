<?php

namespace App\Filament\Resources\TraineeRequestResource\Pages;

use App\Filament\Resources\TraineeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTraineeRequest extends EditRecord
{
    protected static string $resource = TraineeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

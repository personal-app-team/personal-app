<?php

namespace App\Filament\Resources\VacancyTaskResource\Pages;

use App\Filament\Resources\VacancyTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVacancyTask extends EditRecord
{
    protected static string $resource = VacancyTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

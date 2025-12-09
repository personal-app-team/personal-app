<?php

namespace App\Filament\Resources\VacancyRequirementResource\Pages;

use App\Filament\Resources\VacancyRequirementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVacancyRequirement extends EditRecord
{
    protected static string $resource = VacancyRequirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

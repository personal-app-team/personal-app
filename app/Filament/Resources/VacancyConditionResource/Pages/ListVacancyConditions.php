<?php

namespace App\Filament\Resources\VacancyConditionResource\Pages;

use App\Filament\Resources\VacancyConditionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVacancyConditions extends ListRecords
{
    protected static string $resource = VacancyConditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

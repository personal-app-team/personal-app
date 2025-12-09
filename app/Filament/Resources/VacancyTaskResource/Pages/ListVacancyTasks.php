<?php

namespace App\Filament\Resources\VacancyTaskResource\Pages;

use App\Filament\Resources\VacancyTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVacancyTasks extends ListRecords
{
    protected static string $resource = VacancyTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

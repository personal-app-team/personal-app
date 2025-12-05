<?php

namespace App\Filament\Resources\MassPersonnelReportResource\Pages;

use App\Filament\Resources\MassPersonnelReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMassPersonnelReports extends ListRecords
{
    protected static string $resource = MassPersonnelReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

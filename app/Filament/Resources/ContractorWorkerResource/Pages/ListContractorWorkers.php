<?php

namespace App\Filament\Resources\ContractorWorkerResource\Pages;

use App\Filament\Resources\ContractorWorkerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractorWorkers extends ListRecords
{
    protected static string $resource = ContractorWorkerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

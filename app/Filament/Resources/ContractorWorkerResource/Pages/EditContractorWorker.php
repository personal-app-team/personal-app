<?php

namespace App\Filament\Resources\ContractorWorkerResource\Pages;

use App\Filament\Resources\ContractorWorkerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractorWorker extends EditRecord
{
    protected static string $resource = ContractorWorkerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

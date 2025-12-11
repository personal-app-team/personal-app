<?php

namespace App\Filament\Resources\CandidateStatusHistoryResource\Pages;

use App\Filament\Resources\CandidateStatusHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCandidateStatusHistory extends EditRecord
{
    protected static string $resource = CandidateStatusHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

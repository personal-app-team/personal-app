<?php

namespace App\Filament\Resources\CandidateStatusHistoryResource\Pages;

use App\Filament\Resources\CandidateStatusHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCandidateStatusHistories extends ListRecords
{
    protected static string $resource = CandidateStatusHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

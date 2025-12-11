<?php

namespace App\Filament\Resources\CandidateDecisionResource\Pages;

use App\Filament\Resources\CandidateDecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCandidateDecisions extends ListRecords
{
    protected static string $resource = CandidateDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

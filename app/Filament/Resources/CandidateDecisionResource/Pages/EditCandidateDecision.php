<?php

namespace App\Filament\Resources\CandidateDecisionResource\Pages;

use App\Filament\Resources\CandidateDecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCandidateDecision extends EditRecord
{
    protected static string $resource = CandidateDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

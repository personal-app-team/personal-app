<?php

namespace App\Filament\Resources\HiringDecisionResource\Pages;

use App\Filament\Resources\HiringDecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHiringDecisions extends ListRecords
{
    protected static string $resource = HiringDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

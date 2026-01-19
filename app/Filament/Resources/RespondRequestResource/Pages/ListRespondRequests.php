<?php

namespace App\Filament\Resources\RespondRequestResource\Pages;

use App\Filament\Resources\RespondRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRespondRequests extends ListRecords
{
    protected static string $resource = RespondRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

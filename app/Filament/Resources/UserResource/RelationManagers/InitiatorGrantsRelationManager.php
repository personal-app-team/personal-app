<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InitiatorGrantsRelationManager extends RelationManager
{
    protected static string $relationship = 'grantedInitiatorRights';

    protected static ?string $title = 'Выданные права инициатора';

    protected static ?string $label = 'права';

    protected static ?string $pluralLabel = 'Выданные права инициатора';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('initiator.full_name')
                    ->label('Кто выдал')
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('is_temporary')
                    ->label('Временные')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Срок действия')
                    ->date('d.m.Y')
                    ->placeholder('Бессрочно'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Выдано')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => \App\Filament\Resources\InitiatorGrantResource::getUrl('edit', [$record->id])),
            ])
            ->bulkActions([]);
    }
}

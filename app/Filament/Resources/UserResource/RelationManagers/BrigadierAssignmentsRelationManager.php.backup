<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BrigadierAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'brigadierAssignments';

    protected static ?string $title = 'Назначения бригадиром';

    protected static ?string $label = 'назначение';

    protected static ?string $pluralLabel = 'Назначения бригадиром';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('initiator.name')
                    ->label('Назначил')
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('can_create_requests')
                    ->label('Может создавать заявки')
                    ->boolean(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'rejected',
                    ]),
                    
                Tables\Columns\TextColumn::make('assignment_dates_count')
                    ->label('Даты назначений')
                    ->counts('assignment_dates'),
                    
                Tables\Columns\TextColumn::make('confirmed_at')
                    ->label('Подтверждено')
                    ->date('d.m.Y H:i')
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => \App\Filament\Resources\BrigadierAssignmentResource::getUrl('edit', [$record->id])),
            ])
            ->bulkActions([]);
    }
}

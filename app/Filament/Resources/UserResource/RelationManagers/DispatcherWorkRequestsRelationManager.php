<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DispatcherWorkRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'dispatcherRequests';

    protected static ?string $title = 'Заявки как диспетчер';

    protected static ?string $label = 'заявка';

    protected static ?string $pluralLabel = 'Заявки как диспетчер';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('request_number')
            ->columns([
                Tables\Columns\TextColumn::make('request_number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Проект')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('initiator.full_name')
                    ->label('Инициатор')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('brigadier.full_name')
                    ->label('Бригадир')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('work_date')
                    ->label('Дата работ')
                    ->date('d.m.Y')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                        'info' => 'in_progress',
                        'gray' => 'completed',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => \App\Filament\Resources\WorkRequestResource::getUrl('edit', [$record->id])),
            ])
            ->bulkActions([]);
    }
}

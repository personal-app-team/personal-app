<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Assignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Назначения';
    protected static ?string $label = 'назначение';
    protected static ?string $pluralLabel = 'Назначения';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('assignment_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'brigadier_schedule' => 'Бригадир',
                        'work_request' => 'Заявка',
                        'mass_personnel' => 'Массовый',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'brigadier_schedule' => 'primary',
                        'work_request' => 'success', 
                        'mass_personnel' => 'warning',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('workRequest.request_number')
                    ->label('Заявка')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('planned_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('planned_start_time')
                    ->label('Время')
                    ->time('H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('planned_duration_hours')
                    ->label('Часы')
                    ->suffix(' ч')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждено', 
                        'rejected' => 'Отклонено',
                        'completed' => 'Завершено',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'gray',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('assignment_number')
                    ->label('Номер назначения')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('shift_id')
                    ->label('Смена')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !is_null($record->shift_id))
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assignment_type')
                    ->label('Тип назначения')
                    ->options([
                        'brigadier_schedule' => 'Бригадиры',
                        'work_request' => 'Заявки',
                        'mass_personnel' => 'Массовый персонал',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждено',
                        'rejected' => 'Отклонено',
                        'completed' => 'Завершено',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => \App\Filament\Resources\AssignmentResource::getUrl('edit', [$record->id])),
            ])
            ->bulkActions([]);
    }
}

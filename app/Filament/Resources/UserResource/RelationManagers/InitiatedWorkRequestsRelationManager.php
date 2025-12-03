<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InitiatedWorkRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'initiatedRequests';

    protected static ?string $title = 'Созданные заявки';

    protected static ?string $label = 'заявка';

    protected static ?string $pluralLabel = 'Созданные заявки';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('request_number')
                    ->label('Номер заявки')
                    ->required(),
                    
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required(),
                    
                Forms\Components\Select::make('purpose_id')
                    ->relationship('purpose', 'name')
                    ->required(),
                    
                Forms\Components\Select::make('brigadier_id')
                    ->relationship('brigadier', 'name')
                    ->required(),
            ]);
    }

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
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('purpose.name')
                    ->label('Назначение')
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
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'published' => 'Опубликована',
                        'in_progress' => 'В работе',
                        'completed' => 'Завершена',
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

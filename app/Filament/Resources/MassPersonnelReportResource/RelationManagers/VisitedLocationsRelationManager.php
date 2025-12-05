<?php

namespace App\Filament\Resources\MassPersonnelReportResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisitedLocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'visitedLocations';

    protected static ?string $title = 'Посещенные локации';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название локации')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\Textarea::make('address')
                    ->label('Адрес')
                    ->maxLength(65535)
                    ->nullable()
                    ->rows(2),
                    
                Forms\Components\DateTimePicker::make('visited_at')
                    ->label('Дата и время посещения')
                    ->required()
                    ->default(now()),
                    
                Forms\Components\TextInput::make('duration_minutes')
                    ->label('Продолжительность (минуты)')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(60)
                    ->suffix('мин.'),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('Примечания')
                    ->maxLength(65535)
                    ->nullable()
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->limit(30)
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('visited_at')
                    ->label('Посещено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Длительность')
                    ->numeric()
                    ->sortable()
                    ->suffix(' мин.'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Добавлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить локацию'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Изменить'),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }
}

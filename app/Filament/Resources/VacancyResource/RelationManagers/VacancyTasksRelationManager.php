<?php

namespace App\Filament\Resources\VacancyResource\RelationManagers;

use App\Models\VacancyTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VacancyTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'vacancyTasks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('description')
                    ->label('Описание задачи')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                    
                Forms\Components\TextInput::make('order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0)
                    ->helperText('Чем меньше число, тем выше в списке')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->reorderable('order')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('Порядок')
                    ->sortable()
                    ->width(80)
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание задачи')
                    ->wrap()
                    ->limit(200),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить задачу'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Ред.'),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ])
            ->defaultSort('order', 'asc');
    }
}

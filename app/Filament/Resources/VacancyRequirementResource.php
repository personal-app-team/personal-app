<?php
// app/Filament/Resources/VacancyRequirementResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\VacancyRequirementResource\Pages;
use App\Filament\Resources\VacancyRequirementResource\RelationManagers;
use App\Models\VacancyRequirement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VacancyRequirementResource extends Resource
{
    protected static ?string $model = VacancyRequirement::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = '🎯 Подбор персонала';
    protected static ?string $navigationLabel = 'Требования вакансий';
    protected static ?int $navigationSort = 20;
    
    // ⬇️ СКРЫВАЕМ ИЗ НАВИГАЦИИ
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'требование вакансии';
    protected static ?string $pluralModelLabel = 'Требования вакансий';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('vacancy_id')
                            ->label('Вакансия')
                            ->relationship('vacancy', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Описание требования')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('mandatory')
                            ->label('Обязательное требование')
                            ->default(true)
                            ->required(),
                            
                        Forms\Components\TextInput::make('order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0)
                            ->helperText('Чем меньше число, тем выше в списке')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vacancy.title')
                    ->label('Вакансия')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => 
                        $record->vacancy ? 
                        VacancyResource::getUrl('edit', [$record->vacancy_id]) : 
                        null
                    )
                    ->openUrlInNewTab(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(100)
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('mandatory')
                    ->label('Обязательное')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('order')
                    ->label('Порядок')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vacancy_id')
                    ->label('Вакансия')
                    ->relationship('vacancy', 'title')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('mandatory')
                    ->label('Только обязательные')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                    
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

    public static function getRelations(): array
    {
        return [
            // Здесь могут быть RelationManagers если понадобятся
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVacancyRequirements::route('/'),
            'create' => Pages\CreateVacancyRequirement::route('/create'),
            'edit' => Pages\EditVacancyRequirement::route('/{record}/edit'),
        ];
    }
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'hr', 'manager']);
    }
}

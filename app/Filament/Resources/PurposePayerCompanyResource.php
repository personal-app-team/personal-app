<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurposePayerCompanyResource\Pages;
use App\Models\PurposePayerCompany;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurposePayerCompanyResource extends Resource
{
    protected static ?string $model = PurposePayerCompany::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    
    protected static ?string $navigationGroup = '🏗️ Проекты и геолокации';
    
    protected static ?string $navigationLabel = 'Варианты оплаты';
    
    protected static ?int $navigationSort = 50;

    // ДОБАВЛЯЕМ РУССКИЕ LABELS
    protected static ?string $modelLabel = 'вариант оплаты';
    protected static ?string $pluralModelLabel = 'Варианты оплаты';

    public static function getPageLabels(): array
    {
        return [
            'index' => 'Варианты оплаты',
            'create' => 'Создать вариант оплаты',
            'edit' => 'Редактировать вариант оплаты',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('Проект')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive(),
                        
                        Forms\Components\Select::make('purpose_id')
                            ->label('Назначение')
                            ->relationship('purpose', 'name')
                            ->searchable()
                            ->preload()
                            ->options(function ($get) {
                                $projectId = $get('project_id');
                                if (!$projectId) {
                                    return \App\Models\Purpose::all()->pluck('name', 'id');
                                }
                                return \App\Models\Purpose::where('project_id', $projectId)->pluck('name', 'id');
                            })
                            ->required(),
                        
                        Forms\Components\TextInput::make('payer_company')
                            ->label('Компания-плательщик')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('ЦЕХ, БС, ЦФ, УС и т.д.'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Описание варианта')
                            ->rows(2)
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Проект')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('purpose.name')
                    ->label('Назначение')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('payer_company')
                    ->label('Компания-плательщик')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('order')
                    ->label('Порядок')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project')
                    ->relationship('project', 'name'),
                
                Tables\Filters\SelectFilter::make('purpose')
                    ->relationship('purpose', 'name'),
            ])
            // ОБНОВЛЯЕМ ACTIONS С РУССКИМИ НАЗВАНИЯМИ
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            // ОБНОВЛЯЕМ BULK ACTIONS
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ])
            // ДОБАВЛЯЕМ СОРТИРОВКУ ПО УМОЛЧАНИЮ
            ->defaultSort('order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurposePayerCompanies::route('/'),
            'create' => Pages\CreatePurposePayerCompany::route('/create'),
            'edit' => Pages\EditPurposePayerCompany::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurposeResource\Pages;
use App\Models\Purpose;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurposeResource extends Resource
{
    protected static ?string $model = Purpose::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    
    protected static ?string $navigationGroup = '🏗️ Проекты и геолокации';
    
    protected static ?string $navigationLabel = 'Назначения';
    
    protected static ?int $navigationSort = 50;

    // ДОБАВЛЯЕМ РУССКИЕ LABELS
    protected static ?string $modelLabel = 'назначение';
    protected static ?string $pluralModelLabel = 'Назначения';

    public static function getPageLabels(): array
    {
        return [
            'index' => 'Назначения',
            'create' => 'Создать назначение',
            'edit' => 'Редактировать назначение',
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
                            ->preload(),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Название назначения')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Настройки оплаты')
                    ->schema([
                        // НОВОЕ ПОЛЕ: Тип выбора плательщика
                        Forms\Components\Select::make('payer_selection_type')
                            ->label('Тип выбора плательщика')
                            ->options([
                                'strict' => 'Строгая привязка',
                                'optional' => 'Опциональный выбор', 
                                'address_based' => 'Зависит от адреса',
                            ])
                            ->default('strict')
                            ->required()
                            ->live()
                            ->helperText('Определяет как выбирается компания-плательщик'),
                        
                        Forms\Components\TextInput::make('default_payer_company')
                            ->label('Компания-плательщик по умолчанию')
                            ->maxLength(255)
                            ->placeholder('ЦЕХ, БС, ЦФ, УС и т.д.')
                            ->hidden(fn (Forms\Get $get) => $get('payer_selection_type') === 'optional')
                            ->helperText(function (Forms\Get $get) {
                                return match($get('payer_selection_type')) {
                                    'strict' => 'Все заявки будут использовать эту компанию',
                                    'address_based' => 'Используется как запасной вариант если нет правил для адреса',
                                    default => 'Используется для строгой привязки'
                                };
                            }),
                        
                        Forms\Components\Toggle::make('has_custom_payer_selection')
                            ->label('Ручной выбор плательщика')
                            ->helperText('Если включено, можно будет выбирать компанию при создании заявки')
                            ->default(false),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активно')
                            ->default(true),
                    ]),
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
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                // НОВАЯ КОЛОНКА: Тип выбора плательщика
                Tables\Columns\TextColumn::make('payer_selection_type')
                    ->label('Тип оплаты')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state?->value ?? $state) {
                        'strict' => 'Строгая',
                        'optional' => 'Выбор', 
                        'address_based' => 'По адресу',
                        default => $state?->value ?? $state,
                    })
                    ->color(fn ($state) => match($state?->value ?? $state) {
                        'strict' => 'success',
                        'optional' => 'warning',
                        'address_based' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('default_payer_company')
                    ->label('Плательщик')
                    ->limit(20),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50),
                
                Tables\Columns\IconColumn::make('has_custom_payer_selection')
                    ->label('Ручной выбор')
                    ->boolean()
                    ->trueIcon('heroicon-o-hand-raised')
                    ->falseIcon('heroicon-o-cog')
                    ->trueColor('success')
                    ->falseColor('gray'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('payer_companies_count')
                    ->label('Вариантов оплаты')
                    ->counts('payerCompanies'),
                
                Tables\Columns\TextColumn::make('address_rules_count')
                    ->label('Правил по адресам')
                    ->counts('addressRules'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project')
                    ->relationship('project', 'name'),

                // НОВЫЙ ФИЛЬТР: по типу выбора плательщика
                Tables\Filters\SelectFilter::make('payer_selection_type')
                    ->label('Тип оплаты')
                    ->options([
                        'strict' => 'Строгая привязка',
                        'optional' => 'Опциональный выбор',
                        'address_based' => 'По адресу',
                    ]),
                
                Tables\Filters\TernaryFilter::make('has_custom_payer_selection')
                    ->label('Ручной выбор плательщика'),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                Tables\Actions\ViewAction::make()
                    ->label('Просмотреть'),
                Tables\Actions\Action::make('managePayerCompanies')
                    ->label('Варианты оплаты')
                    ->icon('heroicon-o-banknotes')
                    ->url(fn ($record) => \App\Filament\Resources\PurposeResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            // ОБНОВЛЯЕМ BULK ACTIONS
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\RelationManagers\PurposePayerCompaniesRelationManager::class,
            \App\Filament\Resources\RelationManagers\PurposeAddressRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurposes::route('/'),
            'create' => Pages\CreatePurpose::route('/create'),
            'edit' => Pages\EditPurpose::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('edit_database') || 
            auth()->user()->hasPermissionTo('view_purposes');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermissionTo('edit_database');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasPermissionTo('edit_database');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasPermissionTo('edit_database');
    }
}

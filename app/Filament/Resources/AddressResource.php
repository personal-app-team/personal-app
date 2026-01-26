<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AddressResource\Pages;
use App\Models\Address;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected static ?string $navigationGroup = '🏗️ Проекты и геолокации';
    
    protected static ?string $navigationLabel = 'Адреса';
    
    protected static ?int $navigationSort = 50;

    // ДОБАВЛЯЕМ РУССКИЕ LABELS
    protected static ?string $modelLabel = 'адрес';
    protected static ?string $pluralModelLabel = 'Адреса';

    public static function getPageLabels(): array
    {
        return [
            'index' => 'Адреса',
            'create' => 'Создать адрес',
            'edit' => 'Редактировать адрес',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация об адресе')
                    ->schema([
                        Forms\Components\Select::make('projects')
                            ->relationship('projects', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Проекты'),

                        Forms\Components\Toggle::make('is_template')
                            ->label('Это шаблон')
                            ->helperText('Шаблоны доступны для создания адресов в проектах')
                            ->reactive(),

                        Forms\Components\TextInput::make('short_name')
                            ->label(fn ($get) => $get('is_template') 
                                ? 'Название шаблона' 
                                : 'Короткое название адреса')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('full_address')
                            ->label('Полный адрес')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull()
                            ->rules([
                                'required',
                                // ИСПРАВЛЕННЫЙ ВАРИАНТ:
                                function ($get) {
                                    $rule = Rule::unique('addresses', 'full_address')
                                        ->ignore($get('id'));
                                    
                                    // Добавляем условие для шаблонов
                                    if ($get('is_template') !== null) {
                                        $rule = $rule->where('is_template', $get('is_template'));
                                    }
                                    
                                    return $rule;
                                }
                            ])
                            ->helperText(function ($get) {
                                return $get('is_template') 
                                    ? 'Убедитесь, что такого шаблона еще нет'
                                    : 'Убедитесь, что такого адреса еще нет в проектах';
                            }),

                        Forms\Components\Textarea::make('location_type')
                            ->label('Тип локации')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ЗАМЕНЯЕМ: project.name на список проектов
                Tables\Columns\TextColumn::make('projects.name')
                    ->label('Проекты')
                    ->badge()
                    ->separator(',')
                    ->limitList(2)
                    ->searchable(),

                Tables\Columns\TextColumn::make('short_name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('full_address')
                    ->label('Адрес')
                    ->searchable()
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('location_type')
                    ->label('Описание')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_template')
                    ->label('Шаблон')
                    ->boolean(),
                
                // ИСПРАВЛЯЕМ: название счетчика
                Tables\Columns\TextColumn::make('address_rules_count')
                    ->label('Правил оплаты')
                    ->counts('addressRules'),
                
                Tables\Columns\TextColumn::make('work_requests_count')
                    ->label('Заявок')
                    ->counts('workRequests')
                    ->sortable(),

                // ДОБАВЛЯЕМ: количество проектов
                Tables\Columns\TextColumn::make('projects_count')
                    ->label('Проектов')
                    ->counts('projects')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // ОБНОВЛЯЕМ: фильтр для many-to-many
                Tables\Filters\SelectFilter::make('projects')
                    ->relationship('projects', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Проект'),

                Tables\Filters\TernaryFilter::make('is_template')
                    ->label('Тип адреса')
                    ->placeholder('Все адреса')
                    ->trueLabel('Только шаблоны')
                    ->falseLabel('Только адреса проектов'),
            ])
            // ОБНОВЛЯЕМ ACTIONS С РУССКИМИ НАЗВАНИЯМИ
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                Tables\Actions\ViewAction::make()
                    ->label('Просмотреть'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAddresses::route('/'),
            'create' => Pages\CreateAddress::route('/create'),
            'edit' => Pages\EditAddress::route('/{record}/edit'),
        ];
    }
}

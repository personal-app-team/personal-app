<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxStatusResource\Pages;
use App\Models\TaxStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaxStatusResource extends Resource
{
    protected static ?string $model = TaxStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = '⚙️ Справочники и настройки';
    protected static ?string $navigationLabel = 'Налоговые статусы';
    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'налоговый статус';
    protected static ?string $pluralModelLabel = 'Налоговые статусы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('contract_type_id')
                            ->label('Тип договора')
                            ->relationship('contractType', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),
                            
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('НПД 4%, УСН 6%, ОСНО 20%...'),
                            
                        Forms\Components\TextInput::make('tax_rate')
                            ->label('Ставка налога')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.001)
                            ->placeholder('0.04, 0.06, 0.20...')
                            ->helperText('В формате десятичной дроби (4% = 0.04)'),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Описание налогового статуса...')
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Дополнительные настройки')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный')
                            ->default(true)
                            ->helperText('Неактивные статусы не будут доступны для выбора'),
                            
                        Forms\Components\Toggle::make('is_default')
                            ->label('Статус по умолчанию')
                            ->default(false)
                            ->helperText('Будет автоматически выбираться для нового типа договора'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contractType.name')
                    ->label('Тип договора')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('tax_rate')
                    ->label('Ставка')
                    ->formatStateUsing(fn ($state) => ($state * 100) . '%')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state <= 0.06 => 'success',
                        $state <= 0.13 => 'warning',
                        default => 'danger'
                    }),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->searchable()
                    ->placeholder('—'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_default')
                    ->label('По умолчанию')
                    ->boolean()
                    ->trueColor('primary')
                    ->falseColor('gray')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Пользователей')
                    ->counts('users')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'primary' : 'gray'),
                    
                Tables\Columns\TextColumn::make('shifts_count')
                    ->label('Смен')
                    ->counts('shifts')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('contract_type')
                    ->label('Тип договора')
                    ->relationship('contractType', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные')
                    ->placeholder('Все статусы')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
                    
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('По умолчанию')
                    ->placeholder('Все статусы')
                    ->trueLabel('Только по умолчанию')
                    ->falseLabel('Только не по умолчанию'),
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
            ->emptyStateHeading('Нет налоговых статусов')
            ->emptyStateDescription('Создайте первый налоговый статус.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать налоговый статус'),
            ])
            ->defaultSort('contract_type_id', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxStatuses::route('/'),
            'create' => Pages\CreateTaxStatus::route('/create'),
            'edit' => Pages\EditTaxStatus::route('/{record}/edit'),
        ];
    }
}

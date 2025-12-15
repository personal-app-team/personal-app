<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractorRateResource\Pages;
use App\Models\ContractorRate;
use App\Models\Contractor;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContractorRateResource extends Resource
{
    protected static ?string $model = ContractorRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = '⚙️ Справочники и настройки';
    protected static ?string $navigationLabel = 'Ставки подрядчиков';
    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'ставка подрядчика';
    protected static ?string $pluralModelLabel = 'Ставки подрядчиков';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('contractor_id')
                            ->label('Подрядчик')
                            ->relationship('contractor', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live(),

                        Forms\Components\Select::make('category_id')
                            ->label('Категория работ')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Forms\Components\TextInput::make('specialty_name')
                            ->label('Название специальности у подрядчика')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Как эта специальность называется у подрядчика (например: "Монтажник-высотник")'),

                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Ставка (руб/час)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(1)
                            ->placeholder('0')
                            ->helperText('Почасовая ставка для этой специальности'),

                        Forms\Components\Select::make('rate_type')
                            ->label('Тип ставки')
                            ->options([
                                'mass' => 'Массовый персонал',
                                'personalized' => 'Персонализированный',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Массовый - для обезличенного персонала, Персонализированный - для конкретных исполнителей'),
                    ])->columns(2),

                Forms\Components\Section::make('Дополнительные настройки')
                    ->schema([
                        Forms\Components\Toggle::make('is_anonymous')
                            ->label('Обезличенный персонал')
                            ->default(false)
                            ->helperText('Если включено - ставка применяется для обезличенного персонала (без конкретных исполнителей)'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активная ставка')
                            ->default(true)
                            ->helperText('Неактивные ставки не будут использоваться в расчетах'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contractor.name')
                    ->label('Подрядчик')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('specialty_name')
                    ->label('Специальность подрядчика')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rate_type')
                    ->label('Тип ставки')
                    ->formatStateUsing(fn ($state) => $state === 'mass' ? 'Массовая' : 'Персонализированная')
                    ->badge()
                    ->color(fn ($state) => $state === 'mass' ? 'success' : 'primary'),

                Tables\Columns\TextColumn::make('hourly_rate')
                    ->label('Ставка')
                    ->money('RUB')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' ₽/час'),

                Tables\Columns\IconColumn::make('is_anonymous')
                    ->label('Обезличенный')
                    ->boolean()
                    ->trueIcon('heroicon-o-user-group')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('warning')
                    ->falseColor('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('contractor')
                    ->label('Подрядчик')
                    ->relationship('contractor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('rate_type')
                    ->label('Тип ставки')
                    ->options([
                        'mass' => 'Массовая',
                        'personalized' => 'Персонализированная',
                    ]),

                Tables\Filters\TernaryFilter::make('is_anonymous')
                    ->label('Тип персонала')
                    ->placeholder('Все')
                    ->trueLabel('Только обезличенные')
                    ->falseLabel('Только персонализированные'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные ставки')
                    ->placeholder('Все ставки')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
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
            ->emptyStateHeading('Нет ставок подрядчиков')
            ->emptyStateDescription('Создайте первую ставку для подрядчика.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать ставку'),
            ])
            ->defaultSort('contractor_id', 'asc');
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
            'index' => Pages\ListContractorRates::route('/'),
            'create' => Pages\CreateContractorRate::route('/create'),
            'edit' => Pages\EditContractorRate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['contractor', 'category']);
    }
}

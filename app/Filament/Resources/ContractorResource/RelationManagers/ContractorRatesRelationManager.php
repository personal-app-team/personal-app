<?php

namespace App\Filament\Resources\ContractorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractorRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'contractorRates';

    protected static ?string $title = 'Ставки подрядчика';
    protected static ?string $label = 'ставку';
    protected static ?string $pluralLabel = 'Ставки';

    protected static ?string $recordTitleAttribute = 'full_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основные параметры ставки')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $category = \App\Models\Category::find($state);
                                    $set('specialty_name', $category->name . ' (базовая)');
                                }
                            }),
                            
                        Forms\Components\TextInput::make('specialty_name')
                            ->label('Название специальности')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Например: "Электрик" или "Сварщик"')
                            ->helperText('Укажите конкретную специальность или название позиции'),
                            
                        Forms\Components\Select::make('rate_type')
                            ->label('Тип ставки')
                            ->options([
                                'hourly' => 'Почасовая',
                                'daily' => 'Дневная', 
                                'project' => 'Проектная',
                            ])
                            ->default('hourly')
                            ->required()
                            ->native(false),
                    ])->columns(2),

                Forms\Components\Section::make('Финансовые параметры')
                    ->schema([
                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Ставка в час (руб.)')
                            ->numeric()
                            ->required()
                            ->prefix('₽')
                            ->minValue(0)
                            ->step(1)
                            ->default(0)
                            ->helperText('Базовая ставка за 1 час работы'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активная ставка')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Неактивные ставки не будут учитываться при расчетах'),
                    ])->columns(2),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(2)
                            ->nullable()
                            ->columnSpanFull(),
                            
                        Forms\Components\TextInput::make('min_hours')
                            ->label('Минимальное количество часов')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Минимальный заказ (0 = без ограничений)'),
                            
                        Forms\Components\TextInput::make('max_hours')
                            ->label('Максимальное количество часов')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Максимальный заказ в день (0 = без ограничений)'),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->sortable()
                    ->searchable()
                    ->description(fn ($record) => $record->category?->prefix),
                    
                Tables\Columns\TextColumn::make('specialty_name')
                    ->label('Специальность')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('rate_type')
                    ->label('Тип ставки')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'hourly' => 'Почасовая',
                        'daily' => 'Дневная',
                        'project' => 'Проектная',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'hourly' => 'success',
                        'daily' => 'info', 
                        'project' => 'warning',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->label('Ставка')
                    ->money('RUB')
                    ->suffix('/час')
                    ->sortable()
                    ->alignRight()
                    ->description(fn ($record) => $record->rate_type === 'daily' ? 'день' : ''),
                    
                Tables\Columns\TextColumn::make('min_hours')
                    ->label('Мин. часы')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                    
                Tables\Columns\TextColumn::make('max_hours')
                    ->label('Макс. часы')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные')
                    ->placeholder('Все ставки')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
                    
                Tables\Filters\SelectFilter::make('rate_type')
                    ->label('Тип ставки')
                    ->options([
                        'hourly' => 'Почасовая',
                        'daily' => 'Дневная',
                        'project' => 'Проектная',
                    ]),
                    
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить ставку')
                    ->icon('heroicon-o-plus-circle')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Автоматически устанавливаем contractor_id
                        $data['contractor_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('useInShift')
                    ->label('Использовать в смене')
                    ->icon('heroicon-o-clock')
                    ->color('success')
                    ->url(fn ($record) => \App\Filament\Resources\ShiftResource::getUrl('create', [
                        'contractor_rate_id' => $record->id,
                        'contractor_id' => $record->contractor_id,
                    ]))
                    ->visible(fn ($record) => $record->is_active),
                    
                Tables\Actions\EditAction::make()
                    ->label('Редактировать')
                    ->icon('heroicon-o-pencil-square'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить')
                    ->icon('heroicon-o-trash'),
                    
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn ($record) => $record->is_active ? 'Деактивировать' : 'Активировать')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                        
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Активировать выбранные')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                        })
                        ->requiresConfirmation(),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Деактивировать выбранные')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('Нет ставок')
            ->emptyStateDescription('Добавьте первую ставку для этого подрядчика.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить ставку')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['contractor_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->defaultSort('category_id')
            ->defaultSort('hourly_rate', 'desc');
    }
}

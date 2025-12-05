<?php

namespace App\Filament\Resources\MassPersonnelReportResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompensationsRelationManager extends RelationManager
{
    protected static string $relationship = 'compensations';

    protected static ?string $title = 'Компенсации';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Сумма компенсации')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('₽'),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Описание компенсации')
                    ->maxLength(65535)
                    ->required()
                    ->rows(2)
                    ->helperText('Обоснование компенсационной выплаты'),
                    
                Forms\Components\Select::make('type')
                    ->label('Тип компенсации')
                    ->options([
                        'bonus' => 'Бонус',
                        'penalty' => 'Штраф',
                        'additional_payment' => 'Доплата',
                        'other' => 'Прочее',
                    ])
                    ->required()
                    ->default('additional_payment'),
                    
                Forms\Components\DateTimePicker::make('applied_at')
                    ->label('Дата применения')
                    ->required()
                    ->default(now()),
                    
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
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable()
                    ->alignRight(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(30)
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'bonus' => '🏆 Бонус',
                        'penalty' => '⚠️ Штраф',
                        'additional_payment' => '➕ Доплата',
                        'other' => '📝 Прочее',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'bonus' => 'success',
                        'penalty' => 'danger',
                        'additional_payment' => 'warning',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('applied_at')
                    ->label('Применена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип компенсации')
                    ->options([
                        'bonus' => 'Бонус',
                        'penalty' => 'Штраф',
                        'additional_payment' => 'Доплата',
                        'other' => 'Прочее',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить компенсацию'),
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

<?php

namespace App\Filament\Resources\MassPersonnelReportResource\RelationManagers;

use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Операционные расходы';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Тип расхода')
                    ->options(Expense::getTypeOptions())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($set, $state) {
                        if ($state !== 'custom') {
                            $set('custom_type', null);
                        }
                    }),
                    
                Forms\Components\TextInput::make('custom_type')
                    ->label('Название пользовательского типа')
                    ->maxLength(255)
                    ->visible(fn (callable $get) => $get('type') === 'custom')
                    ->required(fn (callable $get) => $get('type') === 'custom'),
                    
                Forms\Components\TextInput::make('amount')
                    ->label('Сумма (руб)')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('₽'),
                    
                Forms\Components\FileUpload::make('receipt_photo')
                    ->label('Фото чека')
                    ->image()
                    ->directory('expenses/receipts')
                    ->maxSize(5120)
                    ->helperText('Максимальный размер: 5MB'),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->rows(2)
                    ->maxLength(65535)
                    ->placeholder('Описание расхода...'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type_display')
                    ->label('Тип расхода')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        str_contains($state, '🚕') => 'warning',
                        str_contains($state, '🛠️') => 'info',
                        str_contains($state, '🍔') => 'success',
                        str_contains($state, '🏨') => 'danger',
                        str_contains($state, '📄') => 'gray',
                        str_contains($state, '📝') => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable()
                    ->alignRight(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(30)
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('receipt_photo')
                    ->label('Чек')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип расхода')
                    ->options(Expense::getTypeOptions()),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить расход'),
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

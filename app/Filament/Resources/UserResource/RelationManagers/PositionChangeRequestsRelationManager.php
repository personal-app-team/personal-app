<?php
// app/Filament/Resources/UserResource/RelationManagers/PositionChangeRequestsRelationManager.php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PositionChangeRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'positionChangeRequests';

    protected static ?string $title = 'Запросы на изменение';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('new_position')
                    ->label('Новая должность')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('new_payment_type')
                    ->label('Тип оплаты')
                    ->options([
                        'rate' => 'Ставка',
                        'salary' => 'Оклад',
                        'combined' => 'Комбинированный',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('new_payment_value')
                    ->label('Сумма оплаты')
                    ->numeric()
                    ->required()
                    ->prefix('₽'),
                Forms\Components\DatePicker::make('effective_date')
                    ->label('Дата вступления в силу')
                    ->required()
                    ->native(false),
                Forms\Components\Textarea::make('reason')
                    ->label('Причина')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('new_position')
            ->columns([
                Tables\Columns\TextColumn::make('new_position')
                    ->label('Новая должность')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('new_payment_type')
                    ->label('Тип оплаты')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'rate' => 'Ставка',
                        'salary' => 'Оклад',
                        'combined' => 'Комбинированный',
                        default => $state
                    }),
                Tables\Columns\TextColumn::make('new_payment_value')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'На рассмотрении',
                        'approved' => 'Утверждено',
                        'rejected' => 'Отклонено',
                        default => $state
                    })
                    ->colors([
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Дата вступления')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Инициатор'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'На рассмотрении',
                        'approved' => 'Утверждено',
                        'rejected' => 'Отклонено',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать запрос')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['requested_by_id'] = auth()->id();
                        $data['user_id'] = $this->getOwnerRecord()->id;
                        
                        // Автоматически заполняем текущие данные
                        if ($this->getOwnerRecord()->currentEmployment) {
                            $employment = $this->getOwnerRecord()->currentEmployment;
                            $data['current_position'] = $employment->position;
                            $data['current_payment_type'] = $employment->payment_type;
                            $data['current_payment_value'] = $employment->salary_amount;
                        }
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

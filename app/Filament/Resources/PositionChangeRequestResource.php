<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionChangeRequestResource\Pages;
use App\Filament\Resources\PositionChangeRequestResource\RelationManagers;
use App\Models\PositionChangeRequest;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PositionChangeRequestResource extends Resource
{
    protected static ?string $model = PositionChangeRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Управление персоналом';
    protected static ?string $navigationLabel = 'Запросы на изменение';
    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'запрос на изменение';
    protected static ?string $pluralModelLabel = 'Запросы на изменение';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о сотруднике')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Сотрудник')
                            ->relationship('user', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user && $user->currentEmployment) {
                                        $employment = $user->currentEmployment;
                                        $set('current_position', $employment->position);
                                        $set('current_payment_type', $employment->payment_type);
                                        $set('current_payment_value', $employment->salary_amount);
                                    }
                                }
                            }),
                    ]),
                
                Forms\Components\Section::make('Текущие условия')
                    ->schema([
                        Forms\Components\TextInput::make('current_position')
                            ->label('Текущая должность')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('current_payment_type')
                            ->label('Текущий тип оплаты')
                            ->disabled()
                            ->options([
                                'rate' => 'Ставка',
                                'salary' => 'Оклад',
                                'combined' => 'Комбинированный',
                            ]),
                        Forms\Components\TextInput::make('current_payment_value')
                            ->label('Текущая сумма оплаты')
                            ->numeric()
                            ->disabled()
                            ->prefix('₽'),
                    ])->columns(3),

                Forms\Components\Section::make('Новые условия')
                    ->schema([
                        Forms\Components\TextInput::make('new_position')
                            ->label('Новая должность')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('new_payment_type')
                            ->label('Новый тип оплаты')
                            ->options([
                                'rate' => 'Ставка',
                                'salary' => 'Оклад',
                                'combined' => 'Комбинированный',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('new_payment_value')
                            ->label('Новая сумма оплаты')
                            ->numeric()
                            ->required()
                            ->prefix('₽'),
                    ])->columns(3),

                Forms\Components\Section::make('Детали запроса')
                    ->schema([
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Дата вступления в силу')
                            ->required()
                            ->native(false)
                            ->minDate(now()),
                        Forms\Components\Textarea::make('reason')
                            ->label('Причина изменения')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('requested_by_id')
                            ->label('Инициатор запроса')
                            ->relationship('requestedBy', 'name')
                            ->default(auth()->id())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('notification_users')
                            ->label('Уведомить пользователей')
                            ->multiple()
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Сотрудник')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_position')
                    ->label('Текущая должность')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('new_position')
                    ->label('Новая должность')
                    ->searchable()
                    ->toggleable(),
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
                Tables\Columns\TextColumn::make('requestedBy.full_name')
                    ->label('Инициатор')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approvedBy.full_name')
                    ->label('Утвердил')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'На рассмотрении',
                        'approved' => 'Утверждено',
                        'rejected' => 'Отклонено',
                    ]),
                Tables\Filters\SelectFilter::make('user')
                    ->label('Сотрудник')
                    ->relationship('user', 'full_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('effective_date')
                    ->label('Будущие изменения')
                    ->query(fn (Builder $query) => $query->where('effective_date', '>', now())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Утвердить')
                    ->icon('heroicon-o-check')
                    ->action(function (PositionChangeRequest $record) {
                        $record->approve(auth()->user());
                    })
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (PositionChangeRequest $record) => "Утверждение изменения для {$record->user->name}")
                    ->modalDescription(fn (PositionChangeRequest $record) => "Вы уверены, что хотите утвердить изменение должности с '{$record->current_position}' на '{$record->new_position}'?")
                    ->visible(fn (PositionChangeRequest $record) => $record->status === 'pending'),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->action(function (PositionChangeRequest $record) {
                        $record->reject(auth()->user());
                    })
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (PositionChangeRequest $record) => "Отклонение изменения для {$record->user->name}")
                    ->modalDescription(fn (PositionChangeRequest $record) => "Вы уверены, что хотите отклонить изменение должности с '{$record->current_position}' на '{$record->new_position}'?")
                    ->visible(fn (PositionChangeRequest $record) => $record->status === 'pending'),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Нет запросов на изменение')
            ->emptyStateDescription('Создайте первый запрос на изменение должности или оплаты.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать запрос на изменение'),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPositionChangeRequests::route('/'),
            'create' => Pages\CreatePositionChangeRequest::route('/create'),
            'edit' => Pages\EditPositionChangeRequest::route('/{record}/edit'),
            // 'view' => Pages\ViewPositionChangeRequest::route('/{record}'),
        ];
    }
}

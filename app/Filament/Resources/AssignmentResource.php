<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use App\Models\User;
use App\Models\WorkRequest;
use App\Models\Address;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Rules\AssignmentDateValidation;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = '👥 Управление персоналом';
    protected static ?string $navigationLabel = 'Назначения на работы';
    protected static ?int $navigationSort = 10;
    protected static ?string $modelLabel = 'назначение на работы';
    protected static ?string $pluralModelLabel = 'Назначения на работы';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Если нет пользователя (консольные команды) - вернуть базовый запрос
        if (!$user) {
            return $query;
        }

        // Исполнитель, contractor_executor, trainee видят только свои
        if ($user->hasAnyRole(['executor', 'contractor_executor', 'trainee'])) {
            return $query->where('user_id', $user->id);
        }

        // Инициатор видит плановые назначения бригадира
        if ($user->hasRole('initiator')) {
            return $query->where('assignment_type', 'brigadier_schedule');
        }

        // Диспетчер видит все типы назначений
        if ($user->hasRole('dispatcher')) {
            return $query->whereIn('assignment_type', ['work_request', 'mass_personnel', 'brigadier_schedule']);
        }

        // HR, contractor_admin не видят назначения вообще
        if ($user->hasAnyRole(['hr', 'contractor_admin'])) {
            return $query->where('id', 0);
        }

        // Для admin, manager - без фильтрации
        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isInitiator = $user->hasRole('initiator');
        $isDispatcher = $user->hasRole('dispatcher');
        $isAdmin = $user->hasRole('admin');
        
        return $form
            ->schema([
                Forms\Components\Section::make('Тип назначения')
                    ->schema([
                        // Поле assignment_type с разными вариантами для разных ролей
                        Forms\Components\Select::make('assignment_type')
                            ->label('Тип назначения')
                            ->options(function () use ($isInitiator, $isDispatcher, $isAdmin) {
                                if ($isInitiator) {
                                    // Инициатор может создавать только плановые назначения бригадира
                                    return [
                                        'brigadier_schedule' => 'Плановое назначение бригадира',
                                    ];
                                } elseif ($isDispatcher || $isAdmin) {
                                    // Диспетчер и админ могут создавать все типы
                                    return [
                                        'brigadier_schedule' => 'Плановое назначение бригадира',
                                        'work_request' => 'Назначение на заявку',
                                        'mass_personnel' => 'Массовый персонал',
                                    ];
                                }
                                // Для остальных ролей - пустой массив (они не должны создавать назначения)
                                return [];
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($set, $state) {
                                $set('work_request_id', null);
                                $set('assignment_number', null);
                                
                                // Автоматически устанавливаем роль для бригадиров
                                if ($state === 'brigadier_schedule') {
                                    $set('role_in_shift', 'brigadier');
                                }
                            })
                            ->visible(fn () => !$isInitiator)
                            ->default(fn () => $isInitiator ? 'brigadier_schedule' : null),
                            
                        // Скрытое поле assignment_type для инициатора
                        Forms\Components\Hidden::make('assignment_type')
                            ->default('brigadier_schedule')
                            ->visible(fn () => $isInitiator),
                    ])->columns(1),

                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label(function (callable $get) {
                                return match($get('assignment_type')) {
                                    'brigadier_schedule' => 'Выбрать Исполнителя на роль Бригадира',
                                    'work_request' => 'Выбрать Исполнителя',
                                    'mass_personnel' => 'Выбрать Подрядчика',
                                    default => 'Пользователь'
                                };
                            })
                            ->options(function (callable $get) {
                                $assignmentType = $get('assignment_type');
                                
                                if ($assignmentType === 'brigadier_schedule' || $assignmentType === 'work_request') {
                                    // Выборка исполнителей (пользователи с ролью executor)
                                    return User::whereHas('roles', function($query) {
                                        $query->where('name', 'executor');
                                    })->get()->pluck('full_name', 'id');
                                } 
                                elseif ($assignmentType === 'mass_personnel') {
                                    // Выборка подрядчиков (пользователи с ролью contractor)
                                    return User::whereHas('roles', function($query) {
                                        $query->where('name', 'contractor');
                                    })->get()->pluck('full_name', 'id');
                                }
                                
                                return User::all()->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive(),

                        Forms\Components\Select::make('work_request_id')
                            ->label('Заявка')
                            ->relationship('workRequest', 'request_number')
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => $get('assignment_type') === 'work_request')
                            ->required(fn (callable $get) => $get('assignment_type') === 'work_request'),

                        Forms\Components\Select::make('role_in_shift')
                            ->label('Роль в смене')
                            ->options([
                                'executor' => 'Исполнитель',
                                'brigadier' => 'Бригадир',
                            ])
                            ->required()
                            ->default('executor')
                            ->disabled(fn (callable $get) => $get('assignment_type') === 'brigadier_schedule')
                            ->dehydrated()
                            ->visible(fn () => !$isInitiator),

                        // Для инициатора - всегда бригадир (скрытое поле)
                        Forms\Components\Hidden::make('role_in_shift')
                            ->default('brigadier')
                            ->visible(fn () => $isInitiator),
                    ])->columns(2),

                Forms\Components\Section::make('Информация о создании')
                    ->schema([
                        // Информация о текущем пользователе (только для просмотра)
                        Forms\Components\Placeholder::make('creator_info')
                            ->label('Создатель назначения')
                            ->content(function () use ($user) {
                                $role = $user->roles->first()->name ?? 'без роли';
                                $roleDisplay = match($role) {
                                    'initiator' => 'Инициатор',
                                    'dispatcher' => 'Диспетчер',
                                    'admin' => 'Администратор (действует как диспетчер)',
                                    'hr' => 'HR (действует как диспетчер)',
                                    'manager' => 'Менеджер (действует как диспетчер)',
                                    'contractor_admin' => 'Админ подрядчика (действует как диспетчер)',
                                    default => ucfirst($role) . ' (действует как диспетчер)'
                                };
                                return "{$user->full_name} - {$roleDisplay}";
                            })
                            ->columnSpanFull(),
                            
                        // Скрытые поля для автоматического заполнения
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => auth()->id()),
                            
                        Forms\Components\Hidden::make('source')
                            ->default(function () use ($user) {
                                // Используем метод из модели для единообразия логики
                                return \App\Models\Assignment::determineSource($user);
                            }),
                    ])
                    ->columnSpanFull()
                    ->visibleOn('create'), // Только при создании

                Forms\Components\Section::make('Планирование')
                    ->schema([
                        Forms\Components\DatePicker::make('planned_date')
                            ->label('Планируемая дата')
                            ->required()
                            ->native(false)
                            ->rules([
                                'required',
                                'date',
                                new AssignmentDateValidation(),
                            ]),

                        Forms\Components\TimePicker::make('planned_start_time')
                            ->label('Время начала')
                            ->seconds(false)
                            ->required()
                            ->default('09:00'),

                        Forms\Components\TextInput::make('planned_duration_hours')
                            ->label('Продолжительность (часов)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(24)
                            ->step(0.5)
                            ->default(8)
                            ->required(),

                        Forms\Components\Select::make('planned_address_id')
                            ->label('Планируемый адрес')
                            ->relationship('plannedAddress', 'short_name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Textarea::make('planned_custom_address')
                            ->label('Неофициальный адрес')
                            ->maxLength(65535)
                            ->rows(2)
                            ->placeholder('Введите адрес вручную...')
                            ->nullable(),

                        Forms\Components\Toggle::make('is_custom_planned_address')
                            ->label('Использовать неофициальный адрес')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('Статус и подтверждение')
                    ->schema([
                        // Поле статуса для не-инициаторов
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает подтверждения',
                                'confirmed' => 'Подтверждено',
                                'rejected' => 'Отклонено',
                                'completed' => 'Завершено',
                            ])
                            ->required()
                            ->default('pending')
                            ->live()
                            ->visible(fn () => !$isInitiator),
                            
                        // Скрытое поле статуса для инициатора
                        Forms\Components\Hidden::make('status')
                            ->default('pending')
                            ->visible(fn () => $isInitiator),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Причина отказа')
                            ->maxLength(65535)
                            ->rows(2)
                            ->visible(fn (callable $get) => $get('status') === 'rejected')
                            ->disabled(fn () => $isInitiator),

                        Forms\Components\DateTimePicker::make('confirmed_at')
                            ->label('Дата подтверждения')
                            ->visible(fn (callable $get) => $get('status') === 'confirmed')
                            ->disabled(true),

                        Forms\Components\DateTimePicker::make('rejected_at')
                            ->label('Дата отклонения')
                            ->visible(fn (callable $get) => $get('status') === 'rejected')
                            ->disabled(true),

                        // Информация о созданной смене
                        Forms\Components\Placeholder::make('shift_info')
                            ->label('Созданная смена')
                            ->content(function ($record) {
                                if ($record?->shift_id) {
                                    $shift = \App\Models\Shift::find($record->shift_id);
                                    return $shift ? "Смена #{$shift->id} ({$shift->status})" : 'Смена не найдена';
                                }
                                return 'Смена не создана';
                            })
                            ->visible(fn ($record) => $record?->shift_id),
                    ])->columns(2),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('assignment_comment')
                            ->label('Комментарий к назначению')
                            ->maxLength(65535)
                            ->rows(3)
                            ->placeholder('Дополнительная информация...')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('assignment_number')
                            ->label('Номер назначения')
                            ->disabled()
                            ->placeholder('Автоматически генерируется')
                            ->visible(fn (callable $get) => $get('assignment_type') === 'brigadier_schedule'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('assignment_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'brigadier_schedule' => 'Бригадир',
                        'work_request' => 'Заявка',
                        'mass_personnel' => 'Массовый',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'brigadier_schedule' => 'primary',
                        'work_request' => 'success',
                        'mass_personnel' => 'warning',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Исполнитель')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.full_name')
                    ->label('Создал')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Источник')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'initiator' => 'Инициатор',
                        'dispatcher' => 'Диспетчер',
                        default => ucfirst($state)
                    })
                    ->color(fn ($state) => match($state) {
                        'initiator' => 'success',
                        'dispatcher' => 'primary',
                        default => 'gray'
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('workRequest.request_number')
                    ->label('Заявка')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('role_in_shift')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'executor' => 'Исполнитель',
                        'brigadier' => 'Бригадир',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'executor' => 'gray',
                        'brigadier' => 'primary',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('planned_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('planned_start_time')
                    ->label('Время')
                    ->time('H:i')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждено',
                        'rejected' => 'Отклонено',
                        'completed' => 'Завершено',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'gray',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('assignment_number')
                    ->label('Номер назначения')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('shift_id')
                    ->label('Смена')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !is_null($record->shift_id))
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assignment_type')
                    ->label('Тип назначения')
                    ->options([
                        'brigadier_schedule' => 'Бригадиры',
                        'work_request' => 'Заявки',
                        'mass_personnel' => 'Массовый персонал',
                    ])
                    ->visible(fn () => auth()->user()->hasAnyRole(['dispatcher', 'admin'])), // Только диспетчер и админ

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждено',
                        'rejected' => 'Отклонено',
                        'completed' => 'Завершено',
                    ]),

                Tables\Filters\SelectFilter::make('role_in_shift')
                    ->label('Роль')
                    ->options([
                        'executor' => 'Исполнитель',
                        'brigadier' => 'Бригадир',
                    ]),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Источник')
                    ->options([
                        'initiator' => 'Инициатор',
                        'dispatcher' => 'Диспетчер',
                    ])
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin', 'dispatcher'])),

                Tables\Filters\Filter::make('has_shift')
                    ->label('Есть смена')
                    ->query(fn ($query) => $query->whereNotNull('shift_id')),

                Tables\Filters\Filter::make('planned_date')
                    ->label('Дата планирования')
                    ->form([
                        Forms\Components\DatePicker::make('planned_from')
                            ->label('С'),
                        Forms\Components\DatePicker::make('planned_until')
                            ->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['planned_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('planned_date', '>=', $date),
                            )
                            ->when(
                                $data['planned_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('planned_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('created_by')
                    ->label('Создатель')
                    ->form([
                        Forms\Components\Select::make('creator_id')
                            ->label('Создатель')
                            ->options(User::whereHas('roles', function ($query) {
                                $query->whereIn('name', ['initiator', 'dispatcher', 'admin']);
                            })->get()->pluck('full_name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['creator_id'],
                                fn (Builder $query, $creatorId): Builder => $query->where('created_by', $creatorId),
                            );
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin', 'dispatcher'])),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать')
                    ->visible(fn (Assignment $record) => auth()->user()->can('update', $record)),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить')
                    ->visible(fn (Assignment $record) => auth()->user()->can('delete', $record)),
                    
                Tables\Actions\Action::make('confirm')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Assignment $record): bool => 
                        $record->status === 'pending' 
                        && auth()->check() 
                        && auth()->user()->can('confirm_assignment', $record)  // ⬅️ Изменено!
                    )
                    ->action(function (Assignment $record): void {
                        if ($record->confirm()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Назначение подтверждено')
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Assignment $record): bool => 
                        $record->status === 'pending' 
                        && auth()->check() 
                        && auth()->user()->can('reject_assignment', $record)  // ⬅️ Изменено!
                    )
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Причина отказа')
                            ->required(),
                    ])
                    ->action(function (Assignment $record, array $data): void {
                        $record->reject($data['rejection_reason']);
                        \Filament\Notifications\Notification::make()
                            ->title('Назначение отклонено')
                            ->success()
                            ->send();
                    }),
                // 1. Кнопка отмены для инициатора
                Tables\Actions\Action::make('cancel_by_initiator')
                    ->label('Отменить (инициатор)')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn (Assignment $record): bool => 
                        $record->isBrigadierSchedule() 
                        && $record->canBeCancelled()
                        && auth()->check() 
                        && auth()->user()->can('cancel_brigadier_assignment') 
                        && $record->created_by === auth()->id()
                    )
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Причина отмены')
                            ->required(),
                    ])
                    ->action(function (Assignment $record, array $data): void {
                        try {
                            $record->cancelByInitiator($data['cancellation_reason']);
                            \Filament\Notifications\Notification::make()
                                ->title('Назначение отменено')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Ошибка')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // 2. Кнопка отмены для админа (любые бригадирские назначения)
                Tables\Actions\Action::make('cancel_any_brigadier')
                    ->label('Отменить любое')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Assignment $record): bool => 
                        $record->isBrigadierSchedule() 
                        && $record->canBeCancelled()
                        && auth()->check() 
                        && auth()->user()->can('cancel_any_brigadier_assignment')
                    )
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Причина отмены')
                            ->required(),
                    ])
                    ->action(function (Assignment $record, array $data): void {
                        try {
                            $record->cancelByInitiator($data['cancellation_reason']);
                            \Filament\Notifications\Notification::make()
                                ->title('Назначение отменено (админ)')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Ошибка')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // 3. Кнопка отказа для исполнителя
                Tables\Actions\Action::make('refuse')
                    ->label('Отказаться')
                    ->icon('heroicon-o-hand-raised')
                    ->color('danger')
                    ->visible(fn (Assignment $record): bool => 
                        $record->canBeRefused()
                        && auth()->check() 
                        && auth()->user()->can('refuse_assignment')
                        && $record->user_id === auth()->id()
                    )
                    ->form([
                        Forms\Components\Textarea::make('refusal_reason')
                            ->label('Причина отказа')
                            ->required(),
                    ])
                    ->action(function (Assignment $record, array $data): void {
                        try {
                            $record->refuse($data['refusal_reason']);
                            \Filament\Notifications\Notification::make()
                                ->title('Вы отказались от назначения')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Ошибка')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // 4. Кнопка отмены для диспетчера (назначения в заявках)
                Tables\Actions\Action::make('cancel_by_dispatcher')
                    ->label('Отменить (диспетчер)')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (Assignment $record): bool => 
                        $record->isWorkRequest() 
                        && $record->canBeCancelled()
                        && auth()->check() 
                        && auth()->user()->can('cancel_work_request_assignment')
                        // Дополнительная проверка: диспетчер может отменять только свои назначения
                        && $record->created_by === auth()->id()
                    )
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Причина отмены')
                            ->required(),
                    ])
                    ->action(function (Assignment $record, array $data): void {
                        try {
                            $record->cancelByDispatcher($data['cancellation_reason']);
                            \Filament\Notifications\Notification::make()
                                ->title('Назначение отменено')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Ошибка')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные')
                        ->visible(fn () => auth()->user()->can('deleteAny', Assignment::class)),
                ]),
            ])
            ->defaultSort('planned_date', 'desc');
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
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }
}

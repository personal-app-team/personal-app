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

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Управление персоналом';
    protected static ?string $navigationLabel = 'Назначения на работы';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'назначение на работы';
    protected static ?string $pluralModelLabel = 'Назначения на работы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Тип назначения')
                    ->schema([
                        Forms\Components\Select::make('assignment_type')
                            ->label('Тип назначения')
                            ->options([
                                'brigadier_schedule' => 'Плановое назначение бригадира',
                                'work_request' => 'Назначение на заявку',
                                'mass_personnel' => 'Массовый персонал',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($set, $state) {
                                $set('work_request_id', null);
                                $set('assignment_number', null);
                                
                                // Автоматически устанавливаем роль для бригадиров
                                if ($state === 'brigadier_schedule') {
                                    $set('role_in_shift', 'brigadier');
                                }
                            }),
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
                            ->visible(fn () => auth()->user()->can('edit_assignments')), // Только для тех, кто может редактировать

                        Forms\Components\Select::make('source')
                            ->label('Источник назначения')
                            ->options([
                                'dispatcher' => 'Диспетчер',
                                'initiator' => 'Инициатор',
                            ])
                            ->required()
                            ->default(function (callable $get) {
                                // Автоматически устанавливаем источник в зависимости от типа
                                return $get('assignment_type') === 'brigadier_schedule' ? 'initiator' : 'dispatcher';
                            })
                            ->disabled() // Делаем поле только для чтения
                            ->dehydrated()
                            ->visible(fn () => auth()->user()->can('edit_assignments')), // Только для админов и тех, кто может редактировать
                    ])->columns(2),

                Forms\Components\Section::make('Планирование')
                    ->schema([
                        Forms\Components\DatePicker::make('planned_date')
                            ->label('Планируемая дата')
                            ->required()
                            ->native(false),

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
                            ->live(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Причина отказа')
                            ->maxLength(65535)
                            ->rows(2)
                            ->visible(fn (callable $get) => $get('status') === 'rejected'),

                        Forms\Components\DateTimePicker::make('confirmed_at')
                            ->label('Дата подтверждения')
                            ->visible(fn (callable $get) => $get('status') === 'confirmed'),

                        Forms\Components\DateTimePicker::make('rejected_at')
                            ->label('Дата отклонения')
                            ->visible(fn (callable $get) => $get('status') === 'rejected'),

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
                    ]),

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
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
                Tables\Actions\Action::make('confirm')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Assignment $record) => $record->status === 'pending')
                    ->action(fn (Assignment $record) => $record->confirm()),

                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Assignment $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Причина отказа')
                            ->required(),
                    ])
                    ->action(function (Assignment $record, array $data): void {
                        $record->reject($data['rejection_reason']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
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

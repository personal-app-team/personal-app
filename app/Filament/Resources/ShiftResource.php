<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Models\Shift;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Учет работ';
    protected static ?string $navigationLabel = 'Смены';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'смена';
    protected static ?string $pluralModelLabel = 'Смены';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        // Поле assignment_number для бригадиров
                        Forms\Components\TextInput::make('assignment_number')
                            ->label('Номер назначения')
                            ->placeholder('ТИ-001/0111-1')
                            ->helperText('Автоматически заполняется для бригадиров')
                            ->disabled()
                            ->visible(fn ($record) => $record?->assignment_number),
                        
                        // Поле request_id для исполнителей
                        Forms\Components\Select::make('request_id')
                            ->label('Заявка')
                            ->relationship('workRequest', 'request_number')
                            ->searchable()
                            ->preload()
                            ->required(fn ($record) => !$record?->assignment_number)
                            ->visible(fn ($record) => !$record?->assignment_number)
                            ->getOptionLabelFromRecordUsing(fn ($record) => (string) ($record->request_number ?? ('WR-' . $record->id))),

                        Forms\Components\Select::make('user_id')
                            ->label('Исполнитель')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state) {
                                    $user = \App\Models\User::find($state);
                                    if ($user && $user->tax_status_id) {
                                        $set('tax_status_id', $user->tax_status_id);
                                    }
                                    if ($user && $user->contract_type_id) {
                                        $set('contract_type_id', $user->contract_type_id);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('contractor_id')
                            ->label('Подрядчик')
                            ->relationship('contractor', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state) {
                                    $contractor = \App\Models\Contractor::find($state);
                                    if ($contractor && $contractor->tax_status_id) {
                                        $set('tax_status_id', $contractor->tax_status_id);
                                    }
                                    if ($contractor && $contractor->contract_type_id) {
                                        $set('contract_type_id', $contractor->contract_type_id);
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('contractor_worker_name')
                            ->label('Имя рабочего от подрядчика')
                            ->maxLength(255)
                            ->visible(fn (callable $get) => $get('contractor_id') && !$get('user_id')),

                        Forms\Components\Select::make('role')
                            ->label('Роль в смене')
                            ->options([
                                'executor' => 'Исполнитель',
                                'brigadier' => 'Бригадир',
                            ])
                            ->required()
                            ->default('executor')
                            ->disabled(fn ($record) => $record?->assignment_number)
                            ->helperText(fn ($record) => $record?->assignment_number ? 'Роль определена назначением' : ''),

                        Forms\Components\Select::make('specialty_id')
                            ->label('Специальность')
                            ->relationship('specialty', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('work_type_id')
                            ->label('Вид работ (для аналитики)')
                            ->relationship('workType', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('contract_type_id')
                            ->label('Тип договора')
                            ->relationship('contractType', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),

                        Forms\Components\Select::make('tax_status_id')
                            ->label('Налоговый статус')
                            ->relationship('taxStatus', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                    ])->columns(2),

                Forms\Components\Section::make('Дата и время')
                    ->schema([
                        Forms\Components\DatePicker::make('work_date')
                            ->label('Дата работы')
                            ->required()
                            ->native(false),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Время начала')
                            ->required()
                            ->live()
                            ->format('H:i')
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state && $get('end_time')) {
                                    self::calculateWorkedTime($state, $get('end_time'), $set);
                                }
                            }),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('Время окончания')
                            ->required()
                            ->live()
                            ->format('H:i')
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state && $get('start_time')) {
                                    self::calculateWorkedTime($get('start_time'), $state, $set);
                                }
                            }),

                        Forms\Components\TextInput::make('worked_hours')
                            ->label('Отработано часов')
                            ->disabled()
                            ->prefix('ч.')
                            ->dehydrated(false)
                            ->formatStateUsing(function ($state, $record) {
                                if ($record) {
                                    return number_format($record->worked_minutes / 60, 2);
                                }
                                return $state ?? '0.00';
                            }),

                        Forms\Components\Hidden::make('worked_minutes')
                            ->reactive()
                            ->required()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && $record->worked_minutes) {
                                    $component->state($record->worked_minutes);
                                }
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Расчет оплаты')
                    ->schema([
                        Forms\Components\TextInput::make('base_rate')
                            ->label('Базовая ставка (руб/час)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Автоматически определяется по специальности')
                            ->live(),

                        Forms\Components\TextInput::make('compensation_amount')
                            ->label('Компенсация')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('₽')
                            ->helperText('Дополнительные выплаты без чека')
                            ->live(),

                        Forms\Components\Textarea::make('compensation_description')
                            ->label('Описание компенсации')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('hand_amount_info')
                            ->label('Сумма на руки (до налога)')
                            ->content(function (callable $get) {
                                $hours = $get('worked_minutes') / 60;
                                $rate = $get('base_rate') ?? 0;
                                $compensation = $get('compensation_amount') ?? 0;
                                $baseAmount = $hours * $rate;
                                $handAmount = $baseAmount + $compensation;
                                return number_format($handAmount, 0, ',', ' ') . ' ₽';
                            })
                            ->extraAttributes(['class' => 'font-bold text-lg text-green-600']),

                        Forms\Components\Placeholder::make('tax_amount_info')
                            ->label('Налог')
                            ->content(function (callable $get) {
                                $hours = $get('worked_minutes') / 60;
                                $rate = $get('base_rate') ?? 0;
                                $compensation = $get('compensation_amount') ?? 0;
                                $baseAmount = $hours * $rate;
                                $handAmount = $baseAmount + $compensation;
                                $taxRate = \App\Models\TaxStatus::find($get('tax_status_id'))?->tax_rate ?? 0;
                                $taxAmount = $handAmount * $taxRate;
                                return number_format($taxAmount, 0, ',', ' ') . ' ₽ (' . ($taxRate * 100) . '%)';
                            })
                            ->extraAttributes(['class' => 'text-red-600']),

                        Forms\Components\Placeholder::make('payout_amount_info')
                            ->label('Сумма к выплате (с налогом)')
                            ->content(function (callable $get) {
                                $hours = $get('worked_minutes') / 60;
                                $rate = $get('base_rate') ?? 0;
                                $compensation = $get('compensation_amount') ?? 0;
                                $baseAmount = $hours * $rate;
                                $handAmount = $baseAmount + $compensation;
                                $taxRate = \App\Models\TaxStatus::find($get('tax_status_id'))?->tax_rate ?? 0;
                                $payoutAmount = $handAmount * (1 + $taxRate);
                                return number_format($payoutAmount, 0, ',', ' ') . ' ₽';
                            })
                            ->extraAttributes(['class' => 'font-bold text-lg text-blue-600']),
                    ])->columns(2),

                Forms\Components\Section::make('Операционные расходы')
                    ->schema([
                        Forms\Components\Placeholder::make('expenses_info')
                            ->label('Операционные расходы')
                            ->content(fn ($record) => $record ? number_format($record->expenses_total, 0, ',', ' ') . ' ₽' : '0 ₽')
                            ->helperText('Транспорт, материалы (по чеку)'),
                    ]),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_paid')
                            ->label('Оплачено')
                            ->default(false)
                            ->helperText('Отметьте, когда смена оплачена'),
                    ]),
            ]);
    }

    protected static function calculateWorkedTime(?string $start, ?string $end, Forms\Set $set): void 
    {
        try {
            // Parse times ensuring proper format
            $startTime = Carbon::parse($start)->setDate(now()->year, now()->month, now()->day);
            $endTime = Carbon::parse($end)->setDate(now()->year, now()->month, now()->day);

            // Handle overnight shifts
            if ($endTime->lt($startTime)) {
                $endTime->addDay();
            }

            $minutes = $startTime->diffInMinutes($endTime);
            
            // Update both fields
            $set('worked_minutes', $minutes);
            $set('worked_hours', number_format($minutes / 60, 2));

            \Log::info('Time calculation:', [
                'start' => $start,
                'end' => $end,
                'minutes' => $minutes,
                'hours' => number_format($minutes / 60, 2)
            ]);
        } catch (\Exception $e) {
            \Log::error('Time calculation error: ' . $e->getMessage());
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('work_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Исполнитель')
                    ->searchable()
                    ->sortable()
                    ->placeholder(fn ($record) => $record->contractor_worker_name ?: '—'),

                Tables\Columns\TextColumn::make('role')
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

                Tables\Columns\TextColumn::make('assignment_number')
                    ->label('Назначение')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('workRequest.request_number')
                    ->label('Заявка')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('specialty.name')
                    ->label('Специальность')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('worked_minutes')
                    ->label('Часы')
                    ->formatStateUsing(fn ($state) => $state ? round($state / 60, 1) . ' ч' : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hand_amount')
                    ->label('На руки')
                    ->money('RUB')
                    ->sortable()
                    ->color('success')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('payout_amount')
                    ->label('К выплате')
                    ->money('RUB')
                    ->sortable()
                    ->color('blue')
                    ->weight('medium'),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Оплата')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'scheduled' => 'gray',
                        'active' => 'warning',
                        'pending_approval' => 'orange',
                        'completed' => 'success',
                        'paid' => 'green',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                // Tables\Columns\TextColumn::make('worked_hours')
                //     ->label('Отработано часов')
                //     ->formatStateUsing(fn ($record) => number_format($record->worked_minutes / 60, 2))
                //     ->suffix(' ч.')
                //     ->alignEnd()
                //     ->sortable(query: function ($query, $direction) {
                //         return $query->orderBy('worked_minutes', $direction);
                //     }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options([
                        'executor' => 'Исполнитель',
                        'brigadier' => 'Бригадир',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'scheduled' => 'Запланирована',
                        'active' => 'Активна',
                        'pending_approval' => 'Ожидает подтверждения',
                        'completed' => 'Завершена',
                        'paid' => 'Оплачена',
                        'cancelled' => 'Отменена',
                    ]),

                Tables\Filters\Filter::make('has_assignment')
                    ->label('Тип основания')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->options([
                                'assignment' => 'По назначению (бригадиры)',
                                'request' => 'По заявке (исполнители)',
                            ])
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['type'] === 'assignment') {
                            return $query->whereNotNull('assignment_number');
                        }
                        if ($data['type'] === 'request') {
                            return $query->whereNotNull('request_id');
                        }
                        return $query;
                    }),

                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Оплата')
                    ->placeholder('Все смены')
                    ->trueLabel('Только оплаченные')
                    ->falseLabel('Только неоплаченные'),
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
            ->defaultSort('work_date', 'desc');
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
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TraineeRequestResource\Pages;
use App\Models\TraineeRequest;
use App\Models\Specialty;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TraineeRequestResource extends Resource
{
    protected static ?string $model = TraineeRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Управление персоналом';
    protected static ?string $navigationLabel = 'Запросы на стажировку';
    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'запрос на стажировку';
    protected static ?string $pluralModelLabel = 'Запросы на стажировку';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Данные кандидата')
                    ->schema([
                        Forms\Components\TextInput::make('candidate_name')
                            ->label('ФИО кандидата')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('candidate_email')
                            ->label('Email кандидата')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('candidate_position')
                            ->label('Должность')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('specialty_id')
                            ->label('Специальность')
                            ->relationship('specialty', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Условия стажировки')
                    ->schema([
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Оплачиваемая стажировка')
                            ->default(false)
                            ->live(),

                        Forms\Components\TextInput::make('proposed_rate')
                            ->label('Ставка (руб/час)')
                            ->numeric()
                            ->minValue(0)
                            ->step(1)
                            ->visible(fn (callable $get) => $get('is_paid'))
                            ->required(fn (callable $get) => $get('is_paid')),

                        Forms\Components\Select::make('duration_days')
                            ->label('Срок стажировки (дней)')
                            ->options([
                                1 => '1 день',
                                2 => '2 дня', 
                                3 => '3 дня',
                                4 => '4 дня',
                                5 => '5 дней',
                                6 => '6 дней',
                                7 => '7 дней',
                            ])
                            ->default(7)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Статус и утверждение')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает HR',
                                'hr_approved' => 'HR утвержден',
                                'hr_rejected' => 'HR отклонен', 
                                'manager_approved' => 'Менеджер утвержден',
                                'active' => 'Активна',
                                'completed' => 'Завершена',
                                'hired' => 'Принят на работу',
                                'rejected' => 'Отказано',
                            ])
                            ->default('pending')
                            ->required()
                            ->disabled(fn () => !auth()->user()->hasRole('admin'))
                            ->visible(fn ($livewire) => $livewire instanceof Pages\CreateTraineeRequest || 
                                                    $livewire instanceof Pages\EditTraineeRequest),

                        Forms\Components\Textarea::make('hr_comment')
                            ->label('Комментарий HR')
                            ->rows(3)
                            ->visible(fn ($get) => in_array($get('status'), ['hr_approved', 'hr_rejected']))
                            ->disabled(fn () => !auth()->user()->hasRole('admin')),

                        Forms\Components\Textarea::make('manager_comment')
                            ->label('Комментарий менеджера')
                            ->rows(3)
                            ->visible(fn ($get) => in_array($get('status'), ['manager_approved', 'rejected']))
                            ->disabled(fn () => !auth()->user()->hasRole('admin')),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Дата начала')
                            ->visible(fn ($get) => in_array($get('status'), ['active', 'completed', 'hired']))
                            ->disabled(fn () => !auth()->user()->hasRole('admin')),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Дата окончания')
                            ->visible(fn ($get) => in_array($get('status'), ['active', 'completed', 'hired']))
                            ->disabled(fn () => !auth()->user()->hasRole('admin')),
                    ])
                    ->visible(fn () => auth()->user()->hasRole('admin')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('candidate_name')
                    ->label('Кандидат')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('candidate_position')
                    ->label('Должность')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('specialty.name')
                    ->label('Специальность')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Инициатор')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // НОВЫЕ КОЛОНКИ ДЛЯ HR И МЕНЕДЖЕРА
                Tables\Columns\TextColumn::make('hrUser.full_name')
                    ->label('HR утвердивший')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('hr_approved_at')
                    ->label('Дата утверждения HR')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('managerUser.full_name')
                    ->label('Менеджер утвердивший')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('manager_approved_at')
                    ->label('Дата утверждения менеджера')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Ожидает HR',
                        'hr_approved' => 'HR утвержден',
                        'hr_rejected' => 'HR отклонен',
                        'manager_approved' => 'Менеджер утвержден', 
                        'active' => 'Активна',
                        'completed' => 'Завершена',
                        'hired' => 'Принят на работу',
                        'rejected' => 'Отказано',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'hr_approved' => 'info',
                        'manager_approved' => 'success',
                        'active' => 'success',
                        'completed' => 'gray',
                        'hired' => 'success',
                        'hr_rejected' => 'danger',
                        'rejected' => 'danger',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Дней')
                    ->suffix(' дн.')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Оплата')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('proposed_rate')
                    ->label('Ставка')
                    ->money('RUB')
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Последнее изменение')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает HR',
                        'hr_approved' => 'HR утвержден',
                        'hr_rejected' => 'HR отклонен',
                        'manager_approved' => 'Менеджер утвержден',
                        'active' => 'Активна',
                        'completed' => 'Завершена',
                        'hired' => 'Принят на работу',
                        'rejected' => 'Отказано',
                    ]),

                Tables\Filters\SelectFilter::make('specialty')
                    ->label('Специальность')
                    ->relationship('specialty', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('is_paid')
                    ->label('Только оплачиваемые')
                    ->query(fn ($query) => $query->where('is_paid', true)),

                Tables\Filters\Filter::make('created_at')
                    ->label('Дата создания')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('С'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать')
                    ->visible(fn (TraineeRequest $record) => auth()->user()->can('update', $record)),

                Tables\Actions\Action::make('approve_hr')
                    ->label('Утвердить HR')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (TraineeRequest $record) => 
                        auth()->user()->can('approveHr', $record) && 
                        $record->status === 'pending'
                    )
                    ->form([
                        Forms\Components\Textarea::make('hr_comment')
                            ->label('Комментарий')
                            ->required(),
                    ])
                    ->action(function (TraineeRequest $record, array $data): void {
                        $record->update([
                            'status' => 'hr_approved',
                            'hr_comment' => $data['hr_comment'],
                            'hr_user_id' => auth()->id(),
                            'hr_approved_at' => now(),
                        ]);
                    }),

                Tables\Actions\Action::make('reject_hr')
                    ->label('Отклонить HR')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (TraineeRequest $record) => 
                        auth()->user()->can('approveHr', $record) && 
                        $record->status === 'pending'
                    )
                    ->form([
                        Forms\Components\Textarea::make('hr_comment')
                            ->label('Причина отказа')
                            ->required(),
                    ])
                    ->action(function (TraineeRequest $record, array $data): void {
                        $record->update([
                            'status' => 'hr_rejected',
                            'hr_comment' => $data['hr_comment'],
                            'hr_user_id' => auth()->id(),
                            'hr_approved_at' => now(),
                        ]);
                    }),

                Tables\Actions\Action::make('approve_manager')
                    ->label('Утвердить менеджером')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (TraineeRequest $record) => 
                        auth()->user()->can('approveManager', $record) && 
                        $record->status === 'hr_approved'
                    )
                    ->form([
                        Forms\Components\Textarea::make('manager_comment')
                            ->label('Комментарий менеджера')
                            ->required(),
                    ])
                    ->action(function (TraineeRequest $record, array $data): void {
                        $record->update([
                            'status' => 'manager_approved',
                            'manager_comment' => $data['manager_comment'],
                            'manager_user_id' => auth()->id(),
                            'manager_approved_at' => now(),
                            // Автоматически устанавливаем даты стажировки
                            'start_date' => now()->addDays(1),
                            'end_date' => now()->addDays(1 + $record->duration_days),
                            'decision_required_at' => now()->addDays(1 + $record->duration_days),
                        ]);
                    }),

                Tables\Actions\Action::make('reject_manager')
                    ->label('Отклонить менеджером')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (TraineeRequest $record) => 
                        auth()->user()->can('approveManager', $record) && 
                        $record->status === 'hr_approved'
                    )
                    ->form([
                        Forms\Components\Textarea::make('manager_comment')
                            ->label('Причина отказа менеджера')
                            ->required(),
                    ])
                    ->action(function (TraineeRequest $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'manager_comment' => $data['manager_comment'],
                            'manager_user_id' => auth()->id(),
                            'manager_approved_at' => now(),
                        ]);
                    }),

                Tables\Actions\Action::make('complete_training')
                    ->label('Завершить стажировку')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (TraineeRequest $record) => 
                        auth()->user()->can('makeDecision', $record) && 
                        $record->status === 'active'
                    )
                    ->form([
                        Forms\Components\Select::make('final_status')
                            ->label('Результат стажировки')
                            ->options([
                                'hired' => 'Принять на работу',
                                'completed' => 'Завершить стажировку',
                                'rejected' => 'Отказать',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('final_comment')
                            ->label('Комментарий'),
                    ])
                    ->action(function (TraineeRequest $record, array $data): void {
                        $record->update([
                            'status' => $data['final_status'],
                        ]);
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Удалить')
                    ->visible(fn (TraineeRequest $record) => auth()->user()->can('delete', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные')
                        ->visible(fn () => auth()->user()->can('manage_trainee_requests')),
                ]),
            ])
            ->emptyStateHeading('Нет запросов на стажировку')
            ->emptyStateDescription('Создайте первый запрос на стажировку.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать запрос')
                    ->visible(fn () => auth()->user()->can('create', TraineeRequest::class)),
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
            'index' => Pages\ListTraineeRequests::route('/'),
            'create' => Pages\CreateTraineeRequest::route('/create'),
            'edit' => Pages\EditTraineeRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        
        // Админы видят всё
        if ($user->hasRole('admin')) {
            return $query;
        }
        
        // HR и Manager видят все запросы
        if ($user->hasRole(['hr', 'manager'])) {
            return $query;
        }

        // Остальные видят только свои запросы
        return $query->where('user_id', $user->id);
    }

    // Автоматически устанавливаем user_id при создании
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }
        return $data;
    }
}
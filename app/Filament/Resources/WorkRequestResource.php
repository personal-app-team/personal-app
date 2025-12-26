<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkRequestResource\Pages;
use App\Models\WorkRequest;
use App\Models\Assignment;
use App\Models\User;
use App\Models\Category;
use App\Models\Project;
use App\Models\Purpose;
use App\Models\Address;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class WorkRequestResource extends Resource
{

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        if ($user->hasAnyRole(['executor', 'contractor_executor', 'trainee'])) {
            return $user->can('view_any_workrequest') || $user->can('view_workrequest');
        }
        
        return true;
    }
    
    protected static ?string $model = WorkRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = '📊 Учет работ';
    protected static ?string $navigationLabel = 'Заявки';
    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'заявка';
    protected static ?string $pluralModelLabel = 'Заявки';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === СЕКЦИЯ 1: ДАТА И ПАРАМЕТРЫ РАБОТ ===
                Forms\Components\Section::make('Дата и параметры работ')
                    ->schema([
                        Forms\Components\DatePicker::make('work_date')
                            ->label('Дата выполнения работ')
                            ->required()
                            ->native(false)
                            ->live(),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Время начала работ')
                            ->required()
                            ->seconds(false)
                            ->displayFormat('H:i'),

                        Forms\Components\TextInput::make('workers_count')
                            ->label('Количество людей')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\TextInput::make('estimated_duration_minutes')
                            ->label('Планируемая продолжительность (часы)')
                            ->numeric()
                            ->required()
                            ->minValue(0.5)
                            ->step(0.5)
                            ->afterStateHydrated(function ($component, $state) {
                                // Преобразуем минуты в часы для отображения
                                if ($state) {
                                    $component->state($state / 60);
                                }
                            })
                            ->dehydrateStateUsing(function ($state) {
                                // Преобразуем часы в минуты для сохранения
                                return (float) $state * 60;
                            })
                            ->helperText('Введите количество часов (0.5 = 30 минут)'),
                    ])->columns(2),

                // === СЕКЦИЯ 2: АДРЕС ВЫПОЛНЕНИЯ РАБОТ ===
                Forms\Components\Section::make('Адрес выполнения работ')
                    ->schema([
                        Forms\Components\Toggle::make('is_custom_address')
                            ->label('Нестандартный адрес')
                            ->live()
                            ->default(false)
                            ->helperText('Отметьте если адреса нет в списке'),

                        Forms\Components\Select::make('address_id')
                            ->label('Официальный адрес')
                            ->relationship('address', 'short_name')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn (Address $record) => $record->short_name . ' - ' . $record->full_address)
                            ->visible(fn ($get) => !$get('is_custom_address')),

                        Forms\Components\Textarea::make('custom_address')
                            ->label('Адрес вручную')
                            ->rows(2)
                            ->maxLength(1000)
                            ->visible(fn ($get) => $get('is_custom_address'))
                            ->helperText('Укажите полный адрес выполнения работ')
                            ->required(fn ($get) => $get('is_custom_address')),
                    ])->columns(1),

                // === СЕКЦИЯ 3: ОСНОВНАЯ ИНФОРМАЦИЯ ===
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('request_number')
                            ->label('Номер заявки')
                            ->disabled()
                            ->default('auto-generated'),

                        Forms\Components\TextInput::make('external_number')
                            ->label('Внешний номер')
                            ->maxLength(255)
                            ->placeholder('Для интеграций с внешними системами'),

                        Forms\Components\Select::make('initiator_id')
                            ->label('Инициатор заявки')
                            ->relationship('initiator', 'name')
                            ->getOptionLabelFromRecordUsing(fn (User $user) => $user->full_name)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(auth()->id())
                            ->visible(fn () => auth()->user()->hasRole('admin'))
                            ->helperText('Только администратор может изменить инициатора'),

                        Forms\Components\Hidden::make('initiator_id')
                            ->default(auth()->id())
                            ->visible(fn () => !auth()->user()->hasRole('admin')),

                        Forms\Components\Select::make('brigadier_id')
                            ->label('Бригадир')
                            ->options(function (callable $get) {
                                $workDate = $get('work_date');
                                
                                if (!$workDate) {
                                    return [];
                                }
                                
                                // Находим бригадиров на выбранную дату
                                $brigadierIds = Assignment::where('assignment_type', 'brigadier_schedule')
                                    ->whereDate('planned_date', $workDate)
                                    ->where('status', 'confirmed')
                                    ->pluck('user_id')
                                    ->toArray();
                                
                                if (empty($brigadierIds)) {
                                    return [];
                                }
                                
                                return User::whereIn('id', $brigadierIds)
                                    ->get()
                                    ->mapWithKeys(fn ($user) => [
                                        $user->id => $user->surname 
                                            ? "{$user->surname} {$user->name}" . ($user->patronymic ? " {$user->patronymic}" : "")
                                            : $user->name
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => !empty($get('work_date')))
                            ->helperText(fn ($get) => 
                                $get('work_date') 
                                    ? 'Выберите бригадира, назначенного на ' . $get('work_date')
                                    : 'Сначала выберите дату работ'
                            ),

                        Forms\Components\TextInput::make('contact_person')
                            ->label('Контактное лицо (если не бригадир)')
                            ->maxLength(255)
                            ->helperText('Укажите ФИО и телефон контактного лица'),

                        Forms\Components\Select::make('category_id')
                            ->label('Категория специалистов')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('work_type_id')
                            ->label('Вид работ')
                            ->relationship('workType', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                // === СЕКЦИЯ 4: ПЕРСОНАЛ ===
                Forms\Components\Section::make('Персонал')
                    ->schema([
                        Forms\Components\Select::make('personnel_type')
                            ->label('Тип персонала')
                            ->options([
                                WorkRequest::PERSONNEL_OUR_STAFF => 'Наш персонал',
                                WorkRequest::PERSONNEL_CONTRACTOR => 'Подрядчик',
                            ])
                            ->required()
                            ->live()
                            ->default(WorkRequest::PERSONNEL_OUR_STAFF)
                            ->rules(['in:our_staff,contractor'])
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Сбрасываем зависимые поля при смене типа
                                if ($state === WorkRequest::PERSONNEL_OUR_STAFF) {
                                    $set('contractor_id', null);
                                    $set('desired_workers', null);
                                }
                            }),

                        Forms\Components\Select::make('contractor_id')
                            ->label('Подрядчик')
                            ->relationship('contractor', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('personnel_type') === WorkRequest::PERSONNEL_CONTRACTOR)
                            ->required(fn ($get) => $get('personnel_type') === WorkRequest::PERSONNEL_CONTRACTOR),

                        Forms\Components\Textarea::make('desired_workers')
                            ->label('Желаемые исполнители (ФИО)')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Иванов Иван, Петров Петр...')
                            ->visible(fn ($get) => $get('personnel_type') === WorkRequest::PERSONNEL_CONTRACTOR)
                            ->helperText('Оставьте пустым для персонализированного персонала подрядчика'),
                    ])->columns(2),

                // === СЕКЦИЯ 5: ПРОЕКТ И НАЗНАЧЕНИЕ ===
                Forms\Components\Section::make('Проект и назначение')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('Проект')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('purpose_id')
                            ->label('Назначение')
                            ->relationship('purpose', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                // === СЕКЦИЯ 6: СТАТУС И ДОПОЛНИТЕЛЬНО ===
                Forms\Components\Section::make('Статус и дополнительно')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                WorkRequest::STATUS_PUBLISHED => 'Опубликована',
                                WorkRequest::STATUS_IN_PROGRESS => 'Взята в работу',
                                WorkRequest::STATUS_CLOSED => 'Заявка закрыта',
                                WorkRequest::STATUS_NO_SHIFTS => 'Смены не открыты',
                                WorkRequest::STATUS_WORKING => 'Выполнение работ',
                                WorkRequest::STATUS_UNCLOSED => 'Смены не закрыты',
                                WorkRequest::STATUS_COMPLETED => 'Заявка завершена',
                                WorkRequest::STATUS_CANCELLED => 'Заявка отменена',
                            ])
                            ->required()
                            ->default(WorkRequest::STATUS_PUBLISHED),

                        Forms\Components\Select::make('dispatcher_id')
                            ->label('Ответственный диспетчер')
                            ->relationship('dispatcher', 'name')
                            ->getOptionLabelFromRecordUsing(fn (User $user) => $user->full_name)
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->default(auth()->id())
                            ->helperText('Автоматически назначается при взятии в работу'),

                        Forms\Components\Textarea::make('additional_info')
                            ->label('Дополнительная информация')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->helperText('ФИО желаемых исполнителей, особые условия и т.д.'),

                        Forms\Components\TextInput::make('total_worked_hours')
                            ->label('Общее кол-во отработанных часов')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.1)
                            ->disabled()
                            ->default(0)
                            ->helperText('Заполняется автоматически после выполнения работ'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request_number')
                    ->label('Номер заявки')
                    ->searchable()
                    ->sortable()
                    ->description(fn (WorkRequest $record): string => 
                        $record->work_date?->format('d.m.Y') ?? 'Дата не указана'
                    ),

                Tables\Columns\TextColumn::make('work_date')
                    ->label('Дата работ')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('initiator.full_name')
                    ->label('Инициатор')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Контактное лицо')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('dispatcher.full_name')
                    ->label('Ответственный диспетчер')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Не назначен')
                    ->toggleable(),

                // Индикаторы по назначениям
                Tables\Columns\TextColumn::make('assignments_summary')
                    ->label('Назначения')
                    ->getStateUsing(function (WorkRequest $record): string {
                        $total = $record->assignments()->count();
                        $confirmed = $record->assignments()->where('status', 'confirmed')->count();
                        $pending = $record->assignments()->where('status', 'pending')->count();
                        
                        if ($total === 0) {
                            return '0/0';
                        }
                        
                        return "✓{$confirmed} ⏳{$pending} 📋{$total}";
                    })
                    ->html()
                    ->color(function (WorkRequest $record): string {
                        $total = $record->assignments()->count();
                        $confirmed = $record->assignments()->where('status', 'confirmed')->count();
                        
                        if ($total === 0) return 'gray';
                        if ($confirmed === $total) return 'success';
                        if ($confirmed > 0) return 'warning';
                        return 'danger';
                    })
                    ->tooltip(function (WorkRequest $record): string {
                        $total = $record->assignments()->count();
                        $confirmed = $record->assignments()->where('status', 'confirmed')->count();
                        $pending = $record->assignments()->where('status', 'pending')->count();
                        $rejected = $record->assignments()->where('status', 'rejected')->count();
                        
                        return "Подтверждено: {$confirmed}\nОжидают: {$pending}\nОтклонено: {$rejected}\nВсего: {$total}";
                    }),

                Tables\Columns\TextColumn::make('workers_count')
                    ->label('Нужно людей')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('personnel_type')
                    ->label('Тип персонала')
                    ->formatStateUsing(fn ($state) => match($state) {
                        WorkRequest::PERSONNEL_OUR_STAFF => 'Наш персонал',
                        WorkRequest::PERSONNEL_CONTRACTOR => 'Подрядчик',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => $state === WorkRequest::PERSONNEL_OUR_STAFF ? 'success' : 'warning')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        WorkRequest::STATUS_PUBLISHED => 'Опубликована',
                        WorkRequest::STATUS_IN_PROGRESS => 'В работе у диспетчера',
                        WorkRequest::STATUS_CLOSED => 'Укомплектована',
                        WorkRequest::STATUS_NO_SHIFTS => 'Смены не созданы',
                        WorkRequest::STATUS_WORKING => 'В работе (смены открыты)',
                        WorkRequest::STATUS_UNCLOSED => 'Смены не закрыты вовремя',
                        WorkRequest::STATUS_COMPLETED => 'Завершена',
                        WorkRequest::STATUS_CANCELLED => 'Отменена',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        WorkRequest::STATUS_PUBLISHED => 'gray',
                        WorkRequest::STATUS_IN_PROGRESS => 'warning',
                        WorkRequest::STATUS_CLOSED => 'success',
                        WorkRequest::STATUS_NO_SHIFTS => 'danger',
                        WorkRequest::STATUS_WORKING => 'primary',
                        WorkRequest::STATUS_UNCLOSED => 'orange',
                        WorkRequest::STATUS_COMPLETED => 'success',
                        WorkRequest::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        WorkRequest::STATUS_PUBLISHED => 'Опубликована',
                        WorkRequest::STATUS_IN_PROGRESS => 'Взята в работу',
                        WorkRequest::STATUS_CLOSED => 'Заявка закрыта',
                        WorkRequest::STATUS_NO_SHIFTS => 'Смены не открыты',
                        WorkRequest::STATUS_WORKING => 'Выполнение работ',
                        WorkRequest::STATUS_UNCLOSED => 'Смены не закрыты',
                        WorkRequest::STATUS_COMPLETED => 'Заявка завершена',
                        WorkRequest::STATUS_CANCELLED => 'Заявка отменена',
                    ]),

                Tables\Filters\SelectFilter::make('personnel_type')
                    ->label('Тип персонала')
                    ->options([
                        WorkRequest::PERSONNEL_OUR_STAFF => 'Наш персонал',
                        WorkRequest::PERSONNEL_CONTRACTOR => 'Подрядчик',
                    ]),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('assigned_to_me')
                    ->label('Назначенные мне')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('dispatcher_id', auth()->id())
                    )
                    ->visible(fn (): bool => auth()->user()->hasRole('dispatcher')),

                Filter::make('published')
                    ->label('Доступные для взятия')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('status', WorkRequest::STATUS_PUBLISHED)
                    )
                    ->visible(fn (): bool => auth()->user()->hasRole('dispatcher')),

                Tables\Filters\Filter::make('work_date')
                    ->label('Дата работ')
                    ->form([
                        Forms\Components\DatePicker::make('work_date_from')
                            ->label('С даты'),
                        Forms\Components\DatePicker::make('work_date_to')
                            ->label('По дату'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['work_date_from'], fn($q, $date) => $q->whereDate('work_date', '>=', $date))
                            ->when($data['work_date_to'], fn($q, $date) => $q->whereDate('work_date', '<=', $date));
                    }),
            ])
            ->actions([
                // Стандартные действия
                Tables\Actions\EditAction::make()
                    ->label('Редактировать')
                    ->visible(fn (WorkRequest $record): bool => 
                        auth()->user()->hasRole('admin') || 
                        (auth()->user()->hasRole('dispatcher') && $record->dispatcher_id === auth()->id())
                    ),
                
                Tables\Actions\ViewAction::make()
                    ->label('Просмотреть'),
                
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить')
                    ->visible(fn (): bool => auth()->user()->hasRole('admin')),

                // Действие для диспетчеров - Взять в работу
                Tables\Actions\Action::make('take_in_progress')
                    ->label('Взять в работу')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Взять заявку в работу')
                    ->modalDescription('Вы уверены, что хотите взять эту заявку в работу? После этого вы станете ответственным диспетчером.')
                    ->action(function (WorkRequest $record) {
                        $record->takeInProgress();
                        
                        Notification::make()
                            ->title('Заявка взята в работу')
                            ->body('Вы теперь ответственный диспетчер по этой заявке')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (WorkRequest $record): bool => 
                        auth()->user()->hasRole('dispatcher') && 
                        $record->status === WorkRequest::STATUS_PUBLISHED
                    ),

                // Действие для диспетчеров - Назначить исполнителей
                Tables\Actions\Action::make('assign_executors')
                    ->label('Назначить исполнителей')
                    ->icon('heroicon-o-user-group')
                    ->color('primary')
                    ->url(fn (WorkRequest $record): string => 
                        AssignmentResource::getUrl('index', [
                            'tableFilters' => [
                                'work_request_id' => [
                                    'value' => $record->id,
                                ],
                            ],
                        ])
                    )
                    ->openUrlInNewTab()
                    ->visible(fn (WorkRequest $record): bool => 
                        auth()->user()->hasRole('dispatcher') && 
                        $record->status === WorkRequest::STATUS_IN_PROGRESS &&
                        $record->dispatcher_id === auth()->id()
                    ),

                // Действие для просмотра назначений
                Tables\Actions\Action::make('view_assignments')
                    ->label('Просмотр назначений')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->url(fn (WorkRequest $record): string => 
                        AssignmentResource::getUrl('index', [
                            'tableFilters' => [
                                'work_request_id' => [
                                    'value' => $record->id,
                                ],
                            ],
                        ])
                    )
                    ->openUrlInNewTab()
                    ->visible(fn (WorkRequest $record): bool => 
                        $record->assignments()->count() > 0
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные')
                        ->visible(fn (): bool => auth()->user()->hasRole('admin')),
                ]),
            ])
            ->defaultSort('work_date', 'desc')
            ->recordUrl(
                fn (WorkRequest $record): string => 
                    auth()->user()->hasRole('dispatcher') && $record->dispatcher_id === auth()->id()
                        ? self::getUrl('edit', ['record' => $record])
                        : self::getUrl('view', ['record' => $record])
            );
    }

    public static function getRelations(): array
    {
        return [
            // Добавим RelationManager для назначений
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkRequests::route('/'),
            'create' => Pages\CreateWorkRequest::route('/create'),
            'edit' => Pages\EditWorkRequest::route('/{record}/edit'),
            'view' => Pages\ViewWorkRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Если пользователь диспетчер, показываем все заявки, но с выделением своих
        if (auth()->user()->hasRole('dispatcher')) {
            return $query;
        }

        return $query;
    }
}

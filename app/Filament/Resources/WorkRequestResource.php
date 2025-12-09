<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkRequestResource\Pages;
use App\Models\WorkRequest;
use App\Models\Category;
use App\Models\Project;
use App\Models\Purpose;
use App\Models\Address;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkRequestResource extends Resource
{
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
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('request_number')
                            ->label('Номер заявки')
                            ->disabled()
                            ->default('auto-generated'),

                        Forms\Components\Select::make('initiator_id')
                            ->label('Инициатор')
                            ->relationship('initiator', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('brigadier_id')
                            ->label('Бригадир')
                            ->relationship('brigadier', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Выберите назначенного бригадира'),

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

                Forms\Components\Section::make('Адрес выполнения работ')
                    ->schema([
                        Forms\Components\Select::make('address_id')
                            ->label('Официальный адрес')
                            ->relationship('address', 'short_name')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn (Address $record) => $record->short_name . ' - ' . $record->full_address)
                            ->visible(fn ($get) => !$get('is_custom_address')),

                        Forms\Components\Toggle::make('is_custom_address')
                            ->label('Нестандартный адрес')
                            ->live()
                            ->default(false)
                            ->helperText('Отметьте если адреса нет в списке'),

                        Forms\Components\Textarea::make('custom_address')
                            ->label('Адрес вручную')
                            ->rows(2)
                            ->maxLength(1000)
                            ->visible(fn ($get) => $get('is_custom_address'))
                            ->helperText('Укажите полный адрес выполнения работ')
                            ->required(fn ($get) => $get('is_custom_address')),
                    ])->columns(1),

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

                Forms\Components\Section::make('Персонал')
                    ->schema([
                        Forms\Components\Select::make('personnel_type')
                            ->label('Тип персонала')
                            ->options([
                                WorkRequest::PERSONNEL_OUR => 'Наш персонал',
                                WorkRequest::PERSONNEL_CONTRACTOR => 'Подрядчик',
                            ])
                            ->required()
                            ->live()
                            ->default(WorkRequest::PERSONNEL_OUR)
                            ->rules(['in:our,contractor'])
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Сбрасываем зависимые поля при смене типа
                                if ($state === WorkRequest::PERSONNEL_OUR) {
                                    $set('contractor_id', null);
                                    $set('mass_personnel_names', null);
                                }
                            }),

                        Forms\Components\Select::make('contractor_id')
                            ->label('Подрядчик')
                            ->relationship('contractor', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('personnel_type') === WorkRequest::PERSONNEL_CONTRACTOR)
                            ->required(fn ($get) => $get('personnel_type') === WorkRequest::PERSONNEL_CONTRACTOR),

                        Forms\Components\Textarea::make('mass_personnel_names')
                            ->label('ФИО массового персонала')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Иванов Иван, Петров Петр...')
                            ->visible(fn ($get) => $get('personnel_type') === WorkRequest::PERSONNEL_CONTRACTOR)
                            ->helperText('Оставьте пустым для персонализированного персонала подрядчика'),
                    ])->columns(2),

                Forms\Components\Section::make('Дата и параметры работ')
                    ->schema([
                        Forms\Components\DatePicker::make('work_date')
                            ->label('Дата выполнения работ')
                            ->required()
                            ->native(false),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Время начала работ')
                            ->required()
                            ->seconds(false)
                            ->displayFormat('H:i'),

                        Forms\Components\TextInput::make('workers_count')
                            ->label('Количество рабочих')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\TextInput::make('estimated_shift_duration')
                            ->label('Ориентировочная продолжительность смены (часы)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->step(0.5),
                    ])->columns(2),

                Forms\Components\Section::make('Статус и дополнительно')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
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
                            ->default('draft'),

                        Forms\Components\Select::make('dispatcher_id')
                            ->label('Диспетчер')
                            ->relationship('dispatcher', 'name')
                            ->searchable()
                            ->preload(),

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
                    ->label('Номер')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('work_date')
                    ->label('Дата работ')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Время начала')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('initiator.full_name')
                    ->label('Инициатор')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Контактное лицо')
                    ->searchable()
                    ->formatStateUsing(fn ($record) => $record->contact_person)
                    ->placeholder('Не указано'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('personnel_type')
                    ->label('Тип персонала')
                    ->formatStateUsing(fn ($state) => match($state) {
                        WorkRequest::PERSONNEL_OUR => 'Наш персонал',
                        WorkRequest::PERSONNEL_CONTRACTOR => 'Подрядчик',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => $state === WorkRequest::PERSONNEL_OUR ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('contractor.name')
                    ->label('Подрядчик')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('workers_count')
                    ->label('Кол-во')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estimated_shift_duration')
                    ->label('Продолжительность')
                    ->suffix(' ч')
                    ->sortable(),

                Tables\Columns\TextColumn::make('mass_personnel_names')
                    ->label('Исполнители')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->mass_personnel_names)
                    ->placeholder('Не указаны'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray',
                        WorkRequest::STATUS_PUBLISHED => 'info',
                        WorkRequest::STATUS_IN_PROGRESS => 'warning',
                        WorkRequest::STATUS_CLOSED => 'success',
                        WorkRequest::STATUS_NO_SHIFTS => 'danger',
                        WorkRequest::STATUS_WORKING => 'primary',
                        WorkRequest::STATUS_UNCLOSED => 'warning',
                        WorkRequest::STATUS_COMPLETED => 'success',
                        WorkRequest::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_worked_hours')
                    ->label('Отработано часов')
                    ->suffix(' ч')
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
                        'draft' => 'Черновик',
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
                        WorkRequest::PERSONNEL_OUR => 'Наш персонал',
                        WorkRequest::PERSONNEL_CONTRACTOR => 'Подрядчик',
                    ]),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

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
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
                Tables\Actions\ViewAction::make()
                    ->label('Просмотреть'),
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
            // Связи со сменами и т.д.
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkRequests::route('/'),
            'create' => Pages\CreateWorkRequest::route('/create'),
            'edit' => Pages\EditWorkRequest::route('/{record}/edit'),
        ];
    }
}

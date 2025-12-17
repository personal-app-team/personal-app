<?php

namespace App\Filament\Resources\ContractorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'workRequests';

    protected static ?string $title = 'Заявки подрядчика';
    protected static ?string $label = 'заявку';
    protected static ?string $pluralLabel = 'Заявки';

    protected static ?string $recordTitleAttribute = 'request_number';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('request_number')
                            ->label('Номер заявки')
                            ->disabled()
                            ->dehydrated(false)
                            ->maxLength(50),
                            
                        Forms\Components\TextInput::make('external_number')
                            ->label('Внешний номер')
                            ->maxLength(50)
                            ->nullable()
                            ->helperText('Номер заявки в системе заказчика'),
                            
                        Forms\Components\DatePicker::make('work_date')
                            ->label('Дата работ')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->closeOnDateSelection(),
                            
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Время начала')
                            ->seconds(false)
                            ->native(false),
                    ])->columns(2),

                Forms\Components\Section::make('Персонал и работы')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Категория персонала')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                            
                        Forms\Components\Select::make('work_type_id')
                            ->label('Вид работ')
                            ->relationship('workType', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                            
                        Forms\Components\TextInput::make('workers_count')
                            ->label('Количество работников')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                            
                        Forms\Components\TextInput::make('estimated_duration_minutes')
                            ->label('Планируемая длительность (минут)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('минут')
                            ->helperText('0 = не ограничено'),
                    ])->columns(2),

                Forms\Components\Section::make('Адрес и объект')
                    ->schema([
                        Forms\Components\Toggle::make('is_custom_address')
                            ->label('Использовать свой адрес')
                            ->reactive()
                            ->inline(false),
                            
                        Forms\Components\Select::make('address_id')
                            ->label('Официальный адрес')
                            ->relationship('address', 'full_address')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->hidden(fn ($get) => $get('is_custom_address'))
                            ->helperText('Выберите из базы адресов'),
                            
                        Forms\Components\Textarea::make('custom_address')
                            ->label('Свой адрес')
                            ->rows(2)
                            ->nullable()
                            ->visible(fn ($get) => $get('is_custom_address'))
                            ->helperText('Укажите полный адрес объекта'),
                            
                        Forms\Components\Select::make('project_id')
                            ->label('Проект')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                            
                        Forms\Components\Select::make('purpose_id')
                            ->label('Задача/цель')
                            ->relationship('purpose', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),

                Forms\Components\Section::make('Контакты и дополнительно')
                    ->schema([
                        Forms\Components\TextInput::make('contact_person')
                            ->label('Контактное лицо на объекте')
                            ->maxLength(255)
                            ->nullable()
                            ->helperText('ФИО и телефон'),
                            
                        Forms\Components\Textarea::make('additional_info')
                            ->label('Дополнительная информация')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull()
                            ->helperText('Особые требования, оборудование и т.д.'),
                            
                        Forms\Components\Textarea::make('desired_workers')
                            ->label('Пожелания к работникам')
                            ->rows(2)
                            ->nullable()
                            ->helperText('Особые навыки, опыт и т.д.'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('request_number')
            ->columns([
                Tables\Columns\TextColumn::make('request_number')
                    ->label('Номер заявки')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->external_number)
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('work_date')
                    ->label('Дата работ')
                    ->date('d.m.Y')
                    ->sortable()
                    ->description(fn ($record) => $record->start_time?->format('H:i')),
                    
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('workers_count')
                    ->label('Работников')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($record) => $record->workers_count > 10 ? 'warning' : 'success'),
                    
                Tables\Columns\TextColumn::make('final_address')
                    ->label('Адрес')
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->final_address;
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'published' => 'Опубликована',
                        'in_progress' => 'В работе у диспетчера',
                        'closed' => 'Укомплектована',
                        'no_shifts' => 'Смены не созданы',
                        'working' => 'В работе (смены открыты)',
                        'unclosed' => 'Смены не закрыты вовремя',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'published' => 'info',
                        'in_progress' => 'primary',
                        'closed' => 'success',
                        'working' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'no_shifts', 'unclosed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('total_worked_hours')
                    ->label('Отработано часов')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' ч')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'published' => 'Опубликована',
                        'in_progress' => 'В работе у диспетчера',
                        'closed' => 'Укомплектована',
                        'working' => 'В работе',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                    ])
                    ->multiple(),
                    
                Tables\Filters\Filter::make('work_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('С'),
                        Forms\Components\DatePicker::make('until')
                            ->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('work_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('work_date', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\TernaryFilter::make('has_shifts')
                    ->label('Есть смены')
                    ->placeholder('Все заявки')
                    ->trueLabel('Только с созданными сменами')
                    ->falseLabel('Только без смен')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('shifts'),
                        false: fn (Builder $query) => $query->whereDoesntHave('shifts'),
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать заявку')
                    ->icon('heroicon-o-plus-circle')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Автоматически устанавливаем contractor_id и personnel_type
                        $data['contractor_id'] = $this->getOwnerRecord()->id;
                        $data['personnel_type'] = 'contractor';
                        
                        // Если не указан статус - ставим по умолчанию
                        if (!isset($data['status'])) {
                            $data['status'] = 'published';
                        }
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('viewShifts')
                    ->label('Смены')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->url(fn ($record) => \App\Filament\Resources\ShiftResource::getUrl('index', [
                        'tableFilters[request_id][values]' => [$record->id]
                    ]))
                    ->badge(fn ($record) => $record->shifts()->count())
                    ->badgeColor('info'),
                    
                Tables\Actions\Action::make('assign')
                    ->label('Назначить')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->url(fn ($record) => \App\Filament\Resources\AssignmentResource::getUrl('create', [
                        'work_request_id' => $record->id,
                        'contractor_id' => $record->contractor_id,
                    ]))
                    ->visible(fn ($record) => in_array($record->status, ['published', 'in_progress'])),
                    
                Tables\Actions\EditAction::make()
                    ->label('Редактировать')
                    ->icon('heroicon-o-pencil-square'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить')
                    ->icon('heroicon-o-trash'),
                    
                Tables\Actions\Action::make('changeStatus')
                    ->label('Изменить статус')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Новый статус')
                            ->options([
                                'published' => 'Опубликована',
                                'in_progress' => 'В работе у диспетчера',
                                'closed' => 'Укомплектована',
                                'working' => 'В работе (смены открыты)',
                                'completed' => 'Завершена',
                                'cancelled' => 'Отменена',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['status' => $data['status']]);
                        
                        // Логируем изменение статуса
                        if ($record->status !== $data['status']) {
                            activity()
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->withProperties([
                                    'old_status' => $record->status,
                                    'new_status' => $data['status'],
                                ])
                                ->log('Изменен статус заявки подрядчика');
                        }
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                        
                    Tables\Actions\BulkAction::make('markAsCompleted')
                        ->label('Отметить как завершенные')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['status' => 'completed']);
                        })
                        ->requiresConfirmation(),
                        
                    Tables\Actions\BulkAction::make('exportToExcel')
                        ->label('Экспорт в Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            // Здесь можно добавить логику экспорта
                        }),
                ]),
            ])
            ->emptyStateHeading('Нет заявок')
            ->emptyStateDescription('Создайте первую заявку для этого подрядчика.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Создать заявку')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['contractor_id'] = $this->getOwnerRecord()->id;
                        $data['personnel_type'] = 'contractor';
                        return $data;
                    }),
            ])
            ->defaultSort('work_date', 'desc')
            ->defaultSort('created_at', 'desc');
    }
}

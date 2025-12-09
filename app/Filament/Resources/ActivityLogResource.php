<?php
// app/Filament/Resources/ActivityLogResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = '👑 Система';
    protected static ?string $navigationLabel = 'История изменений';
    protected static ?int $navigationSort = 70;
    
    protected static ?string $modelLabel = 'запись истории';
    protected static ?string $pluralModelLabel = 'История изменений';
    
    public static function canViewAny(): bool
    {
        return true;
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereDate('created_at', '>=', Carbon::now()->subYear()->toDateString())
            ->orderBy('created_at', 'desc');
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Действие')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Тип объекта')
                    ->formatStateUsing(fn ($state) => match($state) {
                        // Основные модули
                        'App\\Models\\Assignment' => '📋 Назначение',
                        'App\\Models\\User' => '👤 Пользователь',
                        'App\\Models\\Shift' => '💰 Смена',
                        'App\\Models\\WorkRequest' => '📄 Заявка',
                        
                        // Финансы
                        'App\\Models\\Compensation' => '💸 Компенсация',
                        'App\\Models\\ShiftExpense' => '🧾 Расход смены',
                        'App\\Models\\ContractorRate' => '💰 Ставка подрядчика',
                        
                        // Подрядчики
                        'App\\Models\\Contractor' => '🏢 Подрядчик',
                        'App\\Models\\ContractorWorker' => '👷 Работник подрядчика',
                        
                        // Массовый персонал
                        'App\\Models\\MassPersonnelReport' => '👥 Отчет масс. перс.',
                        
                        // Геолокации и фото
                        'App\\Models\\VisitedLocation' => '📍 Посещенная локация',
                        'App\\Models\\Photo' => '📸 Фотография',
                        
                        // Проекты
                        'App\\Models\\Project' => '🏗️ Проект',
                        'App\\Models\\Purpose' => '🎯 Назначение проекта',
                        'App\\Models\\Address' => '📍 Адрес',
                        
                        // Подбор персонала - НОВЫЕ
                        'App\\Models\\Vacancy' => '📋 Вакансия',
                        'App\\Models\\VacancyCondition' => '📝 Условие вакансии',
                        'App\\Models\\VacancyRequirement' => '✅ Требование вакансии',
                        'App\\Models\\VacancyTask' => '📋 Задача вакансии',
                        'App\\Models\\RecruitmentRequest' => '📨 Заявка на подбор',
                        'App\\Models\\Candidate' => '👤 Кандидат',
                        'App\\Models\\Interview' => '🗣️ Собеседование',
                        'App\\Models\\HiringDecision' => '✅ Решение о приеме',
                        'App\\Models\\PositionChangeRequest' => '🔄 Запрос на изменение',
                        'App\\Models\\TraineeRequest' => '🎓 Запрос на стажировку',
                        'App\\Models\\Department' => '🏢 Отдел',
                        'App\\Models\\EmploymentHistory' => '📊 История трудоустройства',
                        
                        // Справочники
                        'App\\Models\\Category' => '📂 Категория',
                        'App\\Models\\Specialty' => '🛠️ Специальность',
                        'App\\Models\\WorkType' => '📋 Вид работ',
                        'App\\Models\\ContractType' => '📄 Тип договора',
                        'App\\Models\\TaxStatus' => '💰 Налоговый статус',
                        'App\\Models\\WorkRequestStatus' => '🚩 Статус заявки',
                        
                        default => class_basename($state),
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID объекта'),
                    
                Tables\Columns\TextColumn::make('event')
                    ->label('Событие')
                    ->badge()
                    ->colors([
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'created' => 'Создание',
                        'updated' => 'Изменение',
                        'deleted' => 'Удаление',
                        'restored' => 'Восстановление',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Время')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Тип объекта')
                    ->options([
                        // Группируем по категориям
                        // === Основные модули ===
                        'App\\Models\\Assignment' => '📋 Назначения',
                        'App\\Models\\User' => '👤 Пользователи',
                        'App\\Models\\Shift' => '💰 Смены',
                        'App\\Models\\WorkRequest' => '📄 Заявки',
                        
                        // === Финансы ===
                        'App\\Models\\Compensation' => '💸 Компенсации',
                        'App\\Models\\ShiftExpense' => '🧾 Расходы смен',
                        'App\\Models\\ContractorRate' => '💰 Ставки подрядчиков',
                        
                        // === Подрядчики ===
                        'App\\Models\\Contractor' => '🏢 Подрядчики',
                        'App\\Models\\ContractorWorker' => '👷 Работники подрядчиков',
                        
                        // === Подбор персонала ===
                        'App\\Models\\Vacancy' => '📋 Вакансии',
                        'App\\Models\\VacancyCondition' => '📝 Условия вакансий',
                        'App\\Models\\VacancyRequirement' => '✅ Требования вакансий',
                        'App\\Models\\VacancyTask' => '📋 Задачи вакансий',
                        'App\\Models\\RecruitmentRequest' => '📨 Заявки на подбор',
                        'App\\Models\\Candidate' => '👤 Кандидаты',
                        'App\\Models\\Interview' => '🗣️ Собеседования',
                        'App\\Models\\HiringDecision' => '✅ Решения о приеме',
                        'App\\Models\\PositionChangeRequest' => '🔄 Запросы на изменение',
                        'App\\Models\\TraineeRequest' => '🎓 Запросы на стажировку',
                        'App\\Models\\Department' => '🏢 Отделы',
                        'App\\Models\\EmploymentHistory' => '📊 История трудоустройства',
                        
                        // === Справочники ===
                        'App\\Models\\Category' => '📂 Категории',
                        'App\\Models\\Specialty' => '🛠️ Специальности',
                        'App\\Models\\WorkType' => '📋 Виды работ',
                        'App\\Models\\ContractType' => '📄 Типы договоров',
                        'App\\Models\\TaxStatus' => '💰 Налоговые статусы',
                        'App\\Models\\WorkRequestStatus' => '🚩 Статусы заявок',
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('event')
                    ->label('Событие')
                    ->options([
                        'created' => 'Создание',
                        'updated' => 'Изменение',
                        'deleted' => 'Удаление',
                        'restored' => 'Восстановление',
                    ]),
                    
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
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Детали записи')
                    ->modalContent(fn ($record) => view(
                        'filament.resources.activity-log-resource.components.log-details',
                        ['log' => $record]
                    )),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}

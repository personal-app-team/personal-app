<?php

namespace App\Filament\Resources\WorkRequestResource\Components;

use App\Models\User;
use App\Models\Assignment;
use App\Models\WorkRequest;
use App\Models\Specialty;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use App\Rules\AssignmentDateValidation;

class AssignExecutorsModal
{
    public static function form(WorkRequest $workRequest): array
    {
        $availableExecutors = self::getAvailableExecutors($workRequest);
        $assignedExecutors = self::getAssignedExecutors($workRequest);
        $alreadyAssigned = $workRequest->assignments()->count();
        $remaining = $workRequest->workers_count - $alreadyAssigned;
        
        // Создаем правило для проверки даты
        $dateRule = new AssignmentDateValidation(
            allowAdmin: true,
            checkWorkRequestDate: $workRequest->work_date
        );
        
        // Проверяем, проходит ли дата валидацию
        $isDateValid = $dateRule->passes('work_date', $workRequest->work_date);
        
        return [
            // Секция с информацией о заявке
            Forms\Components\Section::make('Информация о заявке')
                ->schema([
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\Placeholder::make('request_number')
                                ->label('Номер заявки')
                                ->content($workRequest->request_number),
                            
                            Forms\Components\Placeholder::make('work_date')
                                ->label('Дата работ')
                                ->content($workRequest->work_date?->format('d.m.Y') ?? 'Не указана'),
                            
                            Forms\Components\Placeholder::make('category')
                                ->label('Категория')
                                ->content($workRequest->category?->name ?? 'Не указана'),
                            
                            Forms\Components\Placeholder::make('workers_count')
                                ->label('Требуется людей')
                                ->content($workRequest->workers_count),
                            
                            Forms\Components\Placeholder::make('already_assigned')
                                ->label('Уже назначено')
                                ->content($alreadyAssigned . ' / ' . $workRequest->workers_count),
                        ])
                        ->columns(4),
                    
                    // Простое текстовое предупреждение 1
                    Forms\Components\Placeholder::make('warning_all_assigned')
                        ->label(' ')
                        ->content('⚠️ Все места заняты! Для добавления новых исполнителей сначала отмените существующие назначения.')
                        ->visible(fn () => $alreadyAssigned >= $workRequest->workers_count)
                        ->extraAttributes(['class' => 'text-danger-600 font-medium p-2 bg-danger-50 rounded']),
                    
                    // Простое текстовое предупреждение 2  
                    Forms\Components\Placeholder::make('warning_past_date')
                        ->label(' ')
                        ->content('⚠️ Прошедшая дата! ' . $dateRule->message())
                        ->visible(fn () => !$isDateValid)
                        ->extraAttributes(['class' => 'text-warning-600 font-medium p-2 bg-warning-50 rounded']),
                ])
                ->columns(1),
            
            // Секция для уже назначенных исполнителей
            Forms\Components\Section::make('Уже назначенные исполнители')
                ->schema([
                    Forms\Components\Repeater::make('current_assignments')
                        ->schema([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Placeholder::make('executor_name')
                                        ->label('Исполнитель')
                                        ->content(fn ($state) => 
                                            $state['user']['full_name'] ?? 'Неизвестный'
                                        ),
                                    
                                    Forms\Components\Placeholder::make('specialty')
                                        ->label('Специальность в категории')
                                        ->content(fn ($state) => 
                                            $state['category_specialty'] ?? 'Не указана'
                                        ),
                                    
                                    Forms\Components\Placeholder::make('status')
                                        ->label('Статус')
                                        ->content(fn ($state) => 
                                            match($state['status'] ?? '') {
                                                'pending' => '⏳ Ожидает подтверждения',
                                                'confirmed' => '✅ Подтверждено',
                                                'rejected' => '❌ Отклонено',
                                                'completed' => '✓ Завершено',
                                                default => 'Неизвестно'
                                            }
                                        ),
                                ])
                                ->columns(3),
                        ])
                        ->default(function () use ($assignedExecutors, $workRequest) {
                            return $assignedExecutors->map(function ($assignment) use ($workRequest) {
                                $user = $assignment->user;
                                $specialtyInCategory = $user->getMainSpecialtyInCategory($workRequest->category_id);
                                
                                return [
                                    'user' => [
                                        'full_name' => $user->full_name,
                                    ],
                                    'category_specialty' => $specialtyInCategory?->name ?? 'Не указана',
                                    'status' => $assignment->status
                                ];
                            })->toArray();
                        })
                        ->dehydrated(false)
                        ->disabled()
                        ->itemLabel(fn (array $state): ?string => 
                            $state['user']['full_name'] ?? null
                        )
                        ->collapsible()
                        ->collapseAllAction(null)
                        ->deleteAction(null)
                        ->addAction(null)
                        ->reorderable(false),
                ])
                ->collapsed()
                ->visible(fn () => $assignedExecutors->count() > 0),
            
            // Секция для выбора новых исполнителей
            Forms\Components\Section::make('Добавить новых исполнителей')
                ->schema([
                    // Информация о доступных исполнителях
                    Forms\Components\Placeholder::make('available_info')
                        ->label('Доступные исполнители в категории "' . ($workRequest->category?->name ?? 'Неизвестная') . '"')
                        ->content(function () use ($availableExecutors, $remaining, $workRequest) {
                            if ($availableExecutors->isEmpty()) {
                                return 'Нет доступных исполнителей на эту дату в данной категории.';
                            }
                            
                            $list = $availableExecutors->map(function ($user) use ($workRequest) {
                                $specialtyInCategory = $user->getMainSpecialtyInCategory($workRequest->category_id);
                                $specialty = $specialtyInCategory ? " ({$specialtyInCategory->name})" : ' (Специальность не указана)';
                                return "• {$user->full_name}{$specialty}";
                            })->implode("\n");
                            
                            $count = $availableExecutors->count();
                            return "Найдено {$count} доступных исполнителей:\n\n{$list}";
                        })
                        ->visible(fn () => $remaining > 0 && $availableExecutors->count() > 0),
                    
                    // Выбор исполнителей
                    Forms\Components\Select::make('executor_ids')
                        ->label('Выберите исполнителей')
                        ->options(function () use ($availableExecutors, $workRequest) {
                            return $availableExecutors->mapWithKeys(function ($user) use ($workRequest) {
                                $specialtyInCategory = $user->getMainSpecialtyInCategory($workRequest->category_id);
                                $specialty = $specialtyInCategory ? " ({$specialtyInCategory->name})" : '';
                                return [$user->id => "{$user->full_name}{$specialty}"];
                            });
                        })
                        ->multiple()
                        ->required()
                        ->searchable()
                        ->preload()
                        ->maxItems($remaining)
                        ->disabled(function () use ($remaining, $isDateValid) {
                            // Отключаем если нет мест или дата невалидна
                            return $remaining <= 0 || !$isDateValid;
                        })
                        ->visible(fn () => $remaining > 0)
                        ->rules([
                            // Используем наше правило для валидации
                            function () use ($dateRule) {
                                return function ($attribute, $value, $fail) use ($dateRule) {
                                    if (!$dateRule->passes($attribute, $value)) {
                                        $fail($dateRule->message());
                                    }
                                };
                            }
                        ])
                        ->helperText(function () use ($availableExecutors, $remaining, $workRequest, $isDateValid, $dateRule) {
                            $availableCount = $availableExecutors->count();
                            
                            if ($remaining <= 0) {
                                return 'Все места заняты. Для добавления новых исполнителей сначала отмените существующие назначения.';
                            }
                            
                            if ($availableCount === 0) {
                                return 'Нет доступных исполнителей на эту дату в категории "' . $workRequest->category?->name . '".';
                            }
                            
                            $dateWarning = '';
                            if (!$isDateValid) {
                                $dateWarning = ' ⚠️ ' . $dateRule->message();
                            }
                            
                            return "Можно выбрать до {$remaining} исполнителей. Доступно: {$availableCount} в категории \"{$workRequest->category?->name}\".{$dateWarning}";
                        }),
                    
                    // Роль в смене
                    Forms\Components\Select::make('role_in_shift')
                        ->label('Роль в смене для новых назначений')
                        ->options([
                            'executor' => 'Исполнитель',
                            'brigadier' => 'Бригадир',
                        ])
                        ->default('executor')
                        ->required()
                        ->visible(fn () => $remaining > 0 && $availableExecutors->count() > 0 && $isDateValid),
                    
                    // Комментарий к назначению
                    Forms\Components\Textarea::make('assignment_comment')
                        ->label('Комментарий к назначению')
                        ->rows(3)
                        ->placeholder('Дополнительная информация для исполнителей...')
                        ->columnSpanFull()
                        ->visible(fn () => $remaining > 0 && $availableExecutors->count() > 0 && $isDateValid),
                ])
                ->columns(1)
                ->visible(fn () => $remaining > 0),
        ];
    }
    
    /**
     * Получить доступных исполнителей для заявки с учетом категории
     */
    private static function getAvailableExecutors(WorkRequest $workRequest): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereHas('roles', function ($query) {
                $query->where('name', 'executor');
            })
            // Исполнитель должен иметь специальность в категории заявки
            ->whereHas('specialties', function ($query) use ($workRequest) {
                $query->where('category_id', $workRequest->category_id);
            })
            // Исключаем уже назначенных на эту заявку
            ->whereDoesntHave('assignments', function ($query) use ($workRequest) {
                $query->where('work_request_id', $workRequest->id)
                    ->whereIn('status', ['pending', 'confirmed']);
            })
            // Исключаем тех, кто уже занят на эту дату
            ->whereDoesntHave('assignments', function ($query) use ($workRequest) {
                $query->whereDate('planned_date', $workRequest->work_date)
                    ->whereIn('status', ['pending', 'confirmed']);
            })
            ->with(['specialties' => function ($query) use ($workRequest) {
                $query->where('category_id', $workRequest->category_id);
            }])
            ->orderBy('surname')
            ->orderBy('name')
            ->get();
    }
    
    /**
     * Получить уже назначенных исполнителей для заявки
     */
    private static function getAssignedExecutors(WorkRequest $workRequest): \Illuminate\Database\Eloquent\Collection
    {
        return Assignment::where('work_request_id', $workRequest->id)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->with(['user.specialties' => function ($query) use ($workRequest) {
                $query->where('category_id', $workRequest->category_id);
            }])
            ->get();
    }
    
    /**
     * Обработка массового создания назначений
     */
    public static function handle(WorkRequest $workRequest, array $data): void
    {
        // Создаем правило для проверки даты
        $dateRule = new AssignmentDateValidation(
            allowAdmin: true,
            checkWorkRequestDate: $workRequest->work_date
        );
        
        // Валидация: нельзя назначать на прошедшие даты
        if (!$dateRule->passes('work_date', $workRequest->work_date)) {
            Notification::make()
                ->title('Ошибка: прошедшая дата')
                ->body($dateRule->message())
                ->danger()
                ->send();
            return;
        }
        
        // Если нет executor_ids или пустой массив
        if (empty($data['executor_ids'])) {
            Notification::make()
                ->title('Не выбраны исполнители')
                ->body('Пожалуйста, выберите хотя бы одного исполнителя')
                ->warning()
                ->send();
            return;
        }
        
        $createdCount = 0;
        $alreadyAssigned = $workRequest->assignments()->count();
        $remaining = $workRequest->workers_count - $alreadyAssigned;
        
        // Проверяем, не пытаемся ли назначить больше, чем осталось мест
        $requestedCount = count($data['executor_ids']);
        if ($requestedCount > $remaining) {
            Notification::make()
                ->title('Превышен лимит')
                ->body("Вы пытаетесь назначить {$requestedCount} исполнителей, но осталось только {$remaining} мест")
                ->danger()
                ->send();
            return;
        }
        
        foreach ($data['executor_ids'] as $executorId) {
            // Дополнительная проверка на случай параллельных изменений
            $currentAssignmentsCount = $workRequest->assignments()->count();
            if ($currentAssignmentsCount >= $workRequest->workers_count) {
                Notification::make()
                    ->title('Достигнут лимит работников')
                    ->body("В заявке уже назначено {$workRequest->workers_count} исполнителей")
                    ->warning()
                    ->send();
                break;
            }
            
            // Проверяем, не занят ли исполнитель на эту дату
            $isBusy = Assignment::where('user_id', $executorId)
                ->whereDate('planned_date', $workRequest->work_date)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();
                
            if ($isBusy) {
                $user = User::find($executorId);
                Notification::make()
                    ->title('Исполнитель занят')
                    ->body("{$user->full_name} уже занят на {$workRequest->work_date->format('d.m.Y')}")
                    ->warning()
                    ->send();
                continue;
            }
            
            // Проверяем, есть ли у исполнителя специальность в категории заявки
            $user = User::with(['specialties' => function ($query) use ($workRequest) {
                $query->where('category_id', $workRequest->category_id);
            }])->find($executorId);
            
            if (!$user || $user->specialties->isEmpty()) {
                Notification::make()
                    ->title('Нет подходящей специальности')
                    ->body("У исполнителя {$user->full_name} нет специальности в категории '{$workRequest->category->name}'")
                    ->warning()
                    ->send();
                continue;
            }
            
            // Создаем назначение
            Assignment::create([
                'work_request_id' => $workRequest->id,
                'user_id' => $executorId,
                'role_in_shift' => $data['role_in_shift'] ?? 'executor',
                'assignment_type' => 'work_request',
                'source' => 'dispatcher',
                'planned_date' => $workRequest->work_date,
                'planned_start_time' => $workRequest->start_time,
                'planned_duration_hours' => $workRequest->estimated_duration_minutes / 60,
                'assignment_comment' => $data['assignment_comment'] ?? null,
                'status' => 'pending',
                'created_by' => auth()->id(),
                
                // Копируем адрес из заявки
                'planned_address_id' => $workRequest->address_id,
                'planned_custom_address' => $workRequest->is_custom_address 
                    ? $workRequest->custom_address 
                    : null,
                'is_custom_planned_address' => $workRequest->is_custom_address,
            ]);
            
            $createdCount++;
        }
        
        // Отправляем уведомление
        if ($createdCount > 0) {
            Notification::make()
                ->title('Исполнители назначены')
                ->body("Успешно назначено {$createdCount} исполнителей на заявку")
                ->success()
                ->send();
            
            // Обновляем статус заявки, если все назначены
            $totalAssigned = $workRequest->assignments()->count();
            if ($totalAssigned >= $workRequest->workers_count) {
                $workRequest->markAsClosed();
                
                Notification::make()
                    ->title('Заявка укомплектована')
                    ->body('Все необходимые исполнители назначены')
                    ->success()
                    ->send();
            }
        }
    }
}

<?php

namespace App\Observers;

use App\Models\TraineeRequest;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Facades\Auth;

class TraineeRequestObserver
{
    public function creating(TraineeRequest $traineeRequest)
    {
        if (!$traineeRequest->user_id && Auth::check()) {
            $traineeRequest->user_id = Auth::id();
        }
        
        if (!$traineeRequest->status) {
            $traineeRequest->status = 'pending';
        }
    }

    public function created(TraineeRequest $traineeRequest)
    {
        // Получаем всех пользователей с ролью HR
        $hrUsers = \App\Models\User::role('hr')->get();
        
        if ($hrUsers->isEmpty()) {
            \Log::warning('Нет пользователей с ролью HR для отправки уведомлений');
            return;
        }

        foreach ($hrUsers as $hr) {
            \Filament\Notifications\Notification::make()
                ->title('Новый запрос на стажировку')
                ->body("Кандидат: {$traineeRequest->candidate_name}")
                ->icon('heroicon-o-academic-cap')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url(route('filament.admin.resources.trainee-requests.edit', $traineeRequest))
                        ->label('Просмотреть')
                ])
                ->sendToDatabase($hr);
            
            \Log::info("Уведомление отправлено HR: {$hr->name}");
        }
    }

    public function updated(TraineeRequest $traineeRequest)
    {
        // Уведомление для менеджера при утверждении HR
        if ($traineeRequest->isDirty('status') && $traineeRequest->status === 'hr_approved') {
            $managers = \App\Models\User::whereHas('roles', function($q) {
                $q->where('name', 'manager');
            })->get();

            foreach ($managers as $manager) {
                Notification::make()
                    ->title('Запрос на стажировку утвержден HR')
                    ->body("Кандидат: {$traineeRequest->candidate_name} ожидает вашего утверждения")
                    ->icon('heroicon-o-check')
                    ->success()
                    ->actions([
                        Action::make('view')
                            ->button()
                            ->url(route('filament.admin.resources.trainee-requests.edit', $traineeRequest))
                            ->label('Просмотреть')
                    ])
                    ->sendToDatabase($manager);
            }
        }

        // Уведомление инициатору при утверждении менеджером
        if ($traineeRequest->isDirty('status') && $traineeRequest->status === 'manager_approved') {
            Notification::make()
                ->title('Стажировка утверждена')
                ->body("Кандидат {$traineeRequest->candidate_name} принят на стажировку")
                ->icon('heroicon-o-star')
                ->success()
                ->sendToDatabase($traineeRequest->user);
        }
    }
}

<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Assignment $assignment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database']; // Используем канал базы данных
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'assignment_type' => $this->assignment->assignment_type,
            'planned_date' => $this->assignment->planned_date->format('d.m.Y'),
            'message' => 'Вам назначена ' . ($this->assignment->isBrigadierSchedule() ? 'роль бригадира' : 'работа по заявке'),
            'url' => route('filament.admin.resources.assignments.view', $this->assignment),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Новое назначение')
            ->line('Вам назначена новая задача.')
            ->action('Посмотреть назначение', route('filament.admin.resources.assignments.view', $this->assignment))
            ->line('Спасибо за использование нашей системы!');
    }
}

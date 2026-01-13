<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $assignment;

    public function __construct(Assignment $assignment)
    {
        $this->assignment = $assignment;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $assignmentType = match($this->assignment->assignment_type) {
            'brigadier_schedule' => 'Плановое назначение бригадира',
            'work_request' => 'Назначение на заявку',
            'mass_personnel' => 'Массовое назначение',
            default => 'Назначение'
        };

        return [
            'assignment_id' => $this->assignment->id,
            'assignment_type' => $assignmentType,
            'planned_date' => $this->assignment->planned_date->format('d.m.Y'),
            'planned_time' => $this->assignment->planned_start_time,
            'message' => 'Вам назначена ' . ($this->assignment->isBrigadierSchedule() ? 'роль бригадира' : 'работа по заявке'),
            'url' => '/admin/assignments/' . $this->assignment->id,
            'icon' => 'heroicon-o-user-plus',
            'color' => 'primary',
            'title' => 'Новое назначение',
        ];
    }
}

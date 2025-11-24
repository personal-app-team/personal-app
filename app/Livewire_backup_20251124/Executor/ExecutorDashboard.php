<?php

namespace App\Livewire\Executor;

use App\Models\Assignment;
use App\Models\WorkRequest;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.dashboard')]
class ExecutorDashboard extends Component
{
    use WithPagination;

    public function getPendingAssignmentsProperty()
    {
        return Assignment::with(['workRequests', 'assignment_dates', 'initiator'])
            ->where('brigadier_id', auth()->id())
            ->where('status', 'pending')
            ->whereHas('assignment_dates')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function getConfirmedAssignmentsProperty()
    {
        return Assignment::with(['workRequests', 'assignment_dates', 'initiator'])
            ->where('brigadier_id', auth()->id())
            ->where('status', 'active')
            ->whereHas('assignment_dates')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function confirmAssignment($assignmentId)
    {
        $assignment = Assignment::findOrFail($assignmentId);
        
        if ($assignment->brigadier_id !== auth()->id()) {
            session()->flash('error', 'Ошибка доступа');
            return;
        }

        // Глобальная проверка конфликтов
        $assignmentDates = $assignment->assignment_dates->pluck('work_date');
        
        foreach ($assignmentDates as $date) {
            $existingAssignment = Assignment::where('brigadier_id', auth()->id())
                ->where('status', 'active')
                ->whereHas('assignment_dates', function ($query) use ($date) {
                    $query->where('work_date', $date);
                })
                ->where('id', '!=', $assignmentId)
                ->exists();

            if ($existingAssignment) {
                session()->flash('error', "Вы уже подтвердили работу на дату {$date->format('d.m.Y')} у другого инициатора");
                return;
            }
        }

        // Активируем назначение
        $assignment->update(['status' => 'active']);

        // Обновляем статус заявки
        $workRequest = WorkRequest::where('brigadier_id', auth()->id())
            ->where('status', WorkRequest::STATUS_PENDING_BRIGADIER_CONFIRMATION)
            ->where('initiator_id', $assignment->initiator_id)
            ->first();

        if ($workRequest) {
            $workRequest->update(['status' => WorkRequest::STATUS_PUBLISHED]);
        }

        session()->flash('message', 'Назначение подтверждено! Вы заблокированы на указанные даты для других инициаторов.');
    }

    public function rejectAssignment($assignmentId)
    {
        $assignment = Assignment::findOrFail($assignmentId);
        
        if ($assignment->brigadier_id !== auth()->id()) {
            session()->flash('error', 'Ошибка доступа');
            return;
        }

        $assignment->delete();

        // Сбрасываем бригадира в заявке
        $workRequest = WorkRequest::where('brigadier_id', auth()->id())
            ->where('status', WorkRequest::STATUS_PENDING_BRIGADIER_CONFIRMATION)
            ->where('initiator_id', $assignment->initiator_id)
            ->first();

        if ($workRequest) {
            $workRequest->update([
                'brigadier_id' => null,
                'status' => WorkRequest::STATUS_DRAFT
            ]);
        }

        session()->flash('message', 'Назначение отклонено.');
    }

    public function render()
    {
        return view('livewire.executor.executor-dashboard', [
            'pendingAssignments' => $this->pendingAssignments,
            'confirmedAssignments' => $this->confirmedAssignments,
        ]);
    }
}

<?php

namespace App\Observers;

use App\Models\Assignment;
use App\Models\Shift;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AssignmentObserver
{
    public function creating(Assignment $assignment): void
    {
        // Автоматически заполняем created_by
        if (!$assignment->created_by && Auth::check()) {
            $assignment->created_by = Auth::id();
        }
    }

    public function updated(Assignment $assignment): void
    {
        // Если это назначение бригадира, статус изменился на "confirmed" и смена еще не создана
        if ($assignment->isBrigadierSchedule() && 
            $assignment->isDirty('status') && 
            $assignment->isConfirmed() && 
            !$assignment->shift_id) {
            $this->createShiftFromConfirmedAssignment($assignment);
        }
        
        // Логируем изменение статуса в файл активности
        if ($assignment->isDirty('status')) {
            $oldStatus = $assignment->getOriginal('status');
            $newStatus = $assignment->status;
            
            Log::channel('activity')->info("Изменен статус назначения", [
                'assignment_id' => $assignment->id,
                'from' => $oldStatus,
                'to' => $newStatus,
                'user_id' => $assignment->user_id,
                'assignment_number' => $assignment->assignment_number,
            ]);
        }
    }
    
    /**
     * Создать смену на основе подтвержденного назначения бригадира
     */
    private function createShiftFromConfirmedAssignment(Assignment $assignment): void
    {
        $shift = Shift::create([
            'user_id' => $assignment->user_id,
            'work_date' => $assignment->planned_date,
            'start_time' => $assignment->planned_start_time,
            'role' => 'brigadier',
            'status' => 'scheduled',
            'assignment_number' => $assignment->assignment_number,
            'specialty_id' => $assignment->user->specialties()->first()?->id,
            'work_type_id' => null,
            'address_id' => $assignment->planned_address_id,
            'base_rate' => $assignment->user->specialties()->first()?->base_hourly_rate ?? 0,
            'planned_duration_hours' => $assignment->planned_duration_hours,
        ]);

        // Связываем смену с назначением
        $assignment->update(['shift_id' => $shift->id]);
    }

    /**
     * Handle the Assignment "created" event.
     */
    public function created(Assignment $assignment): void
    {
        // Автоматически генерируем номер назначения для бригадиров
        if ($assignment->isBrigadierSchedule() && !$assignment->assignment_number) {
            $this->generateAssignmentNumber($assignment);
        }
    }

    /**
     * Сгенерировать номер назначения для бригадира
     */
    private function generateAssignmentNumber(Assignment $assignment): void
    {
        // Здесь можно добавить логику генерации номера
        // Пока используем простой вариант
        $initiator = $assignment->user;
        $initials = $this->getInitials($initiator->full_name);
        $sequence = Assignment::where('user_id', $assignment->user_id)
            ->whereDate('created_at', today())
            ->count();
        
        $datePart = now()->format('dm');
        $number = "{$initials}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT) . "/{$datePart}";
        
        $assignment->update(['assignment_number' => $number]);
    }

    /**
     * Получить инициалы из ФИО
     */
    private function getInitials($fullName): string
    {
        $parts = explode(' ', $fullName);
        $initials = '';
        
        if (isset($parts[0])) $initials .= mb_substr($parts[0], 0, 1);
        if (isset($parts[1])) $initials .= mb_substr($parts[1], 0, 1);
        
        return mb_strtoupper($initials);
    }
}

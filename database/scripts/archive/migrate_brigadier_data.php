<?php

use Illuminate\Support\Facades\DB;

class MigrateBrigadierData 
{
    public function run()
    {
        echo "Начинаем миграцию данных из BrigadierAssignment в Assignment..." . PHP_EOL;
        
        $brigadierAssignments = DB::table('brigadier_assignments')->get();
        
        foreach ($brigadierAssignments as $brigAssign) {
            echo "Обрабатываем BrigadierAssignment ID: {$brigAssign->id}" . PHP_EOL;
            
            // Получаем даты назначения
            $assignmentDates = DB::table('brigadier_assignment_dates')
                ->where('assignment_id', $brigAssign->id)
                ->get();
            
            foreach ($assignmentDates as $date) {
                echo "  - Дата: {$date->assignment_date}, статус: {$date->status}" . PHP_EOL;
                
                // Создаем новое назначение в единой системе
                $newAssignmentId = DB::table('assignments')->insertGetId([
                    'assignment_type' => 'brigadier_schedule',
                    'user_id' => $brigAssign->brigadier_id,
                    'role_in_shift' => 'brigadier',
                    'source' => 'initiator',
                    'planned_date' => $date->assignment_date,
                    'assignment_number' => $date->assignment_number,
                    'planned_start_time' => $date->planned_start_time,
                    'planned_duration_hours' => $date->planned_duration_hours,
                    'assignment_comment' => $brigAssign->comment,
                    'status' => $date->status,
                    'confirmed_at' => $date->confirmed_at,
                    'rejected_at' => $date->rejected_at,
                    'rejection_reason' => $date->rejection_reason,
                    'planned_address_id' => $brigAssign->planned_address_id,
                    'planned_custom_address' => $brigAssign->planned_custom_address,
                    'is_custom_planned_address' => $brigAssign->is_custom_planned_address,
                    'shift_id' => $date->shift_id,
                    'created_at' => $brigAssign->created_at ?? now(),
                    'updated_at' => now(),
                ]);
                
                echo "  + Создано Assignment ID: {$newAssignmentId}" . PHP_EOL;
                
                // Обновляем связь в смене если есть
                if ($date->shift_id) {
                    DB::table('shifts')
                        ->where('id', $date->shift_id)
                        ->update([
                            'assignment_number' => $date->assignment_number,
                            'updated_at' => now()
                        ]);
                    echo "  + Обновлена смена ID: {$date->shift_id}" . PHP_EOL;
                }
            }
        }
        
        $migratedCount = DB::table('assignments')->where('assignment_type', 'brigadier_schedule')->count();
        echo "Миграция завершена! Перенесено назначений: {$migratedCount}" . PHP_EOL;
    }
}

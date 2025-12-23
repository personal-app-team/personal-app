<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class FixPermissionDuplicates extends Command
{
    protected $signature = 'permissions:fix-duplicates
                          {--dry-run : Показать что будет изменено без применения}
                          {--keep-underscore : Оставить версии с подчеркиванием}
                          {--keep-no-underscore : Оставить версии без подчеркивания}';

    protected $description = 'Исправить дублирующиеся разрешения с разным написанием';

    // Пары дубликатов (без_подчеркивания => с_подчеркиванием)
    private $duplicatePairs = [
        // Основные сущности
        'workrequest' => 'work_request',
        'recruitmentrequest' => 'recruitment_request',
        'traineerequest' => 'trainee_request',
        'masspersonnelreport' => 'mass_personnel_report',
        'visitedlocation' => 'visited_location',
        'contractorworker' => 'contractor_worker',
        'employmenthistory' => 'employment_history',
        'workrequeststatus' => 'work_request_status',
        'activitylog' => 'activity_log',
        'purposetemplate' => 'purpose_template',
        'addressproject' => 'address_project',
        'addresstemplate' => 'address_template',
        'candidatedecision' => 'candidate_decision',
        'candidatestatushistory' => 'candidate_status_history',
        'contractorrate' => 'contractor_rate',
        'contracttype' => 'contract_type',
        'initiatorgrant' => 'initiator_grant',
        'positionchangerequest' => 'position_change_request',
        'purposeaddressrule' => 'purpose_address_rule',
        'purposepayercompany' => 'purpose_payer_company',
        'taxstatus' => 'tax_status',
        'vacancycondition' => 'vacancy_condition',
        'vacancyrequirement' => 'vacancy_requirement',
        'vacancytask' => 'vacancy_task',
    ];

    private $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete', 'delete_any', 'restore_any', 'force_delete_any', 'replicate'];

    public function handle()
    {
        $this->info('🔍 Поиск дублирующихся разрешений...');

        $dryRun = $this->option('dry-run');
        $keepUnderscore = $this->option('keep-underscore');
        $keepNoUnderscore = $this->option('keep-no-underscore');
        
        // По умолчанию оставляем версии с подчеркиванием (более читаемо)
        $keepUnderscore = $keepUnderscore || (!$keepNoUnderscore && !$keepUnderscore);
        
        $totalFixed = 0;
        $totalDeleted = 0;
        
        foreach ($this->duplicatePairs as $noUnderscore => $withUnderscore) {
            $keep = $keepUnderscore ? $withUnderscore : $noUnderscore;
            $remove = $keepUnderscore ? $noUnderscore : $withUnderscore;
            
            $this->processPermissionsForModel($keep, $remove, $dryRun, $totalFixed, $totalDeleted);
        }
        
        if ($dryRun) {
            $this->warn("✅ Сухой пройден. Будет исправлено: {$totalFixed}, удалено: {$totalDeleted}");
            $this->info("Для применения запустите без --dry-run");
        } else {
            $this->info("🎉 Исправлено: {$totalFixed} разрешений, удалено: {$totalDeleted} дубликатов");
            $this->info("Не забудьте очистить кэш разрешений: sail artisan permission:cache-reset");
        }
        
        return Command::SUCCESS;
    }
    
    private function processPermissionsForModel(string $keep, string $remove, bool $dryRun, int &$fixed, int &$deleted): void
    {
        foreach ($this->actions as $action) {
            $keepPermissionName = "{$action}_{$keep}";
            $removePermissionName = "{$action}_{$remove}";
            
            $keepPermission = Permission::where('name', $keepPermissionName)->first();
            $removePermission = Permission::where('name', $removePermissionName)->first();
            
            if (!$removePermission) {
                continue; // Нет дубликата для этого действия
            }
            
            if (!$keepPermission) {
                // Просто переименовываем remove в keep
                if (!$dryRun) {
                    $removePermission->update(['name' => $keepPermissionName]);
                }
                $this->line("📝 Переименовано: {$removePermissionName} → {$keepPermissionName}");
                $fixed++;
                continue;
            }
            
            // Оба существуют, нужно объединить
            if (!$dryRun) {
                $this->mergePermissions($keepPermission, $removePermission);
            } else {
                $this->info("🔄 Объединение: {$removePermissionName} → {$keepPermissionName}");
                $roleCount = DB::table('role_has_permissions')->where('permission_id', $removePermission->id)->count();
                $userCount = DB::table('model_has_permissions')->where('permission_id', $removePermission->id)->count();
                $this->line("   Будет перенесено ролей: {$roleCount}");
                $this->line("   Будет перенесено пользователей: {$userCount}");
            }
            $fixed++;
            $deleted++;
        }
    }
    
    private function mergePermissions(Permission $keep, Permission $remove): void
    {
        $this->info("🔄 Объединение: {$remove->name} → {$keep->name}");
        
        // Используем транзакцию для безопасности
        DB::transaction(function () use ($keep, $remove) {
            // 1. Переносим связи с ролями (избегаем дубликатов)
            $roles = DB::table('role_has_permissions')
                ->where('permission_id', $remove->id)
                ->select('role_id')
                ->get();
            
            foreach ($roles as $role) {
                // Проверяем, нет ли уже такой связи
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $role->role_id)
                    ->where('permission_id', $keep->id)
                    ->exists();
                
                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $role->role_id,
                        'permission_id' => $keep->id,
                    ]);
                }
            }
            
            // 2. Переносим прямые назначения пользователям (избегаем дубликатов)
            $users = DB::table('model_has_permissions')
                ->where('permission_id', $remove->id)
                ->select('model_type', 'model_id')
                ->get();
            
            foreach ($users as $user) {
                // Проверяем, нет ли уже такой связи
                $exists = DB::table('model_has_permissions')
                    ->where('model_type', $user->model_type)
                    ->where('model_id', $user->model_id)
                    ->where('permission_id', $keep->id)
                    ->exists();
                
                if (!$exists) {
                    DB::table('model_has_permissions')->insert([
                        'permission_id' => $keep->id,
                        'model_type' => $user->model_type,
                        'model_id' => $user->model_id,
                    ]);
                }
            }
            
            // 3. Удаляем старые связи
            DB::table('role_has_permissions')->where('permission_id', $remove->id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $remove->id)->delete();
            
            // 4. Удаляем само разрешение
            $remove->delete();
        });
        
        $this->line("   ✅ Перенесено в {$keep->name}");
    }
}

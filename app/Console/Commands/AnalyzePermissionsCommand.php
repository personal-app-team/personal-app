<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class AnalyzePermissionsCommand extends Command
{
    protected $signature = 'system:analyze-permissions
                            {--fix : Исправить проблемы автоматически}
                            {--details : Показать детальную информацию}';
    
    protected $description = 'Анализ системы разрешений Spatie';

    public function handle()
    {
        $this->info('🔍 Анализ системы разрешений Spatie...');

        // 1. Проверка таблиц Spatie
        $this->checkSpatieTables();

        // 2. Проверка дубликатов разрешений
        $duplicates = $this->checkDuplicatePermissions();

        // 3. Проверка guard
        $this->checkGuardNames();

        // 4. Интеграция с Filament
        $this->checkFilamentIntegration();

        // 5. Рекомендации
        $this->showRecommendations($duplicates);

        if ($this->option('fix') && count($duplicates) > 0) {
            $this->fixDuplicatePermissions($duplicates);
        }

        return Command::SUCCESS;
    }

    private function checkSpatieTables()
    {
        $this->info('📋 Стандартные таблицы Spatie Permission:');
        
        $tables = [
            'permissions' => 'Разрешения',
            'roles' => 'Роли',
            'model_has_permissions' => 'Связь моделей с разрешениями',
            'model_has_roles' => 'Связь моделей с ролями',
            'role_has_permissions' => 'Связь ролей с разрешениями'
        ];

        foreach ($tables as $table => $description) {
            try {
                $exists = DB::select("SHOW TABLES LIKE '{$table}'");
                $count = $exists ? DB::table($table)->count() : 0;
                $status = $exists ? '✅' : '❌';
                $this->line("   {$status} {$table}: {$description} ({$count} записей)");
                
                if ($this->option('details') && $exists) {
                    $sample = DB::table($table)->first();
                    $this->line("       Пример: " . json_encode($sample));
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Ошибка при проверке таблицы {$table}: " . $e->getMessage());
            }
        }
    }

    private function checkDuplicatePermissions(): array
    {
        $this->info("\n📊 Анализ разрешений на дубликаты:");
        
        $permissions = Permission::all();
        $permissionNames = [];
        $duplicates = [];

        foreach ($permissions as $permission) {
            $name = $permission->name;
            if (in_array($name, $permissionNames)) {
                $duplicates[] = $name;
            }
            $permissionNames[] = $name;
        }

        if (count($duplicates) > 0) {
            $this->warn('   ⚠️  Найдены дубликаты разрешений:');
            foreach ($duplicates as $dup) {
                $this->line("      - {$dup}");
            }
        } else {
            $this->info('   ✅ Дубликатов нет');
        }

        return $duplicates;
    }

    private function checkGuardNames()
    {
        $this->info("\n🛡️ Guard name для разрешений:");
        
        $guards = Permission::select('guard_name')->distinct()->get();
        
        foreach ($guards as $guard) {
            $count = Permission::where('guard_name', $guard->guard_name)->count();
            $this->line("   - {$guard->guard_name}: {$count} разрешений");
        }
    }

    private function checkFilamentIntegration()
    {
        $this->info("\n🎯 Интеграция с Filament:");
        
        $filamentPermissions = Permission::where('name', 'like', '%_any_%')
            ->orWhere('name', 'like', 'access_filament')
            ->get();

        if ($filamentPermissions->count() > 0) {
            $this->info('   ✅ Найдены разрешения Filament');
            $this->line('   Примеры:');
            foreach ($filamentPermissions->take(5) as $perm) {
                $this->line("      - {$perm->name} (guard: {$perm->guard_name})");
            }
        } else {
            $this->info('   ℹ️  Не найдено разрешений Filament');
        }
    }

    private function showRecommendations(array $duplicates)
    {
        $this->info("\n💡 Рекомендации:");
        $this->line('   1. 5 таблиц - норма для Spatie Laravel Permission');
        $this->line('   2. ' . Permission::count() . ' разрешений (проверить на избыточность)');
        $this->line('   3. Проверить конфигурацию в config/permission.php');
        $this->line('   4. Убедиться, что guard_name везде "web" (если не используется API)');
        
        if (count($duplicates) > 0) {
            $this->warn('   5. Исправьте дубликаты разрешений командой: php artisan system:analyze-permissions --fix');
        }
    }

    private function fixDuplicatePermissions(array $duplicates)
    {
        if (!$this->confirm('Исправить дубликаты разрешений автоматически?')) {
            return;
        }

        foreach ($duplicates as $dup) {
            $permissions = Permission::where('name', $dup)->get();
            if ($permissions->count() > 1) {
                // Оставляем первое разрешение, удаляем остальные
                $first = $permissions->first();
                Permission::where('name', $dup)->where('id', '!=', $first->id)->delete();
                $this->info("   ✅ Исправлено дублирование: {$dup}");
            }
        }
    }
}

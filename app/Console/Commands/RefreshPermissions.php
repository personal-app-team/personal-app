<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\File;

class RefreshPermissions extends Command
{
    protected $signature = 'permissions:refresh';
    protected $description = 'Обновить разрешения на основе матрицы доступа';

    /**
     * Группы для разрешений
     */
    private array $permissionGroups = [
        'user' => 'user',
        'assignment' => 'assignment',
        'workrequest' => 'work_request',
        'shift' => 'shift',
        'expense' => 'expense',
        'candidate' => 'candidate',
        'vacancy' => 'vacancy',
        'recruitmentrequest' => 'recruitment',
        'interview' => 'recruitment',
        'hiringdecision' => 'recruitment',
        'contractor' => 'contractor',
        'project' => 'project',
        'address' => 'address',
        'category' => 'category',
        'specialty' => 'specialty',
        'worktype' => 'work_type',
        'contracttype' => 'contract_type',
        'taxstatus' => 'tax_status',
        'department' => 'department',
        'employmenthistory' => 'employment_history',
        'positionchangerequest' => 'position_change',
        'traineerequest' => 'trainee',
        'activitylog' => 'activity_log',
        'masspersonnelreport' => 'mass_personnel',
    ];

    /**
     * Сопоставление типов доступа с разрешениями
     */
    private array $accessTypeToPermissions = [
        '👁️' => ['view_any', 'view'],                    // Только просмотр
        '✅' => ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'], // Полный доступ
        '🔐' => [],                                       // Ограниченный доступ (определяется отдельно)
    ];

    public function handle()
    {
        $this->info('🔄 Обновление разрешений на основе матрицы доступа...');
        
        // 1. Проверяем наличие матрицы
        if (!File::exists('docs/access_matrix.csv')) {
            $this->error('❌ Файл матрицы доступа не найден: docs/access_matrix.csv');
            $this->info('💡 Создайте матрицу: echo "Resource,Model,admin,initiator,dispatcher,executor,contractor_admin,contractor_dispatcher,contractor_executor,hr,manager,trainee,viewer,notes" > docs/access_matrix.csv');
            return 1;
        }
        
        // 2. Читаем матрицу
        $matrix = $this->readAccessMatrix();
        if (empty($matrix)) {
            return 1;
        }
        
        // 3. Читаем таблицу ограниченного доступа
        $limitedAccess = $this->readLimitedAccessTable();
        
        // 4. Генерируем разрешения
        $this->generatePermissions($matrix, $limitedAccess);
        
        // 5. Обновляем RoleSeeder
        $this->updateRoleSeeder($matrix, $limitedAccess);
        
        $this->info('🎉 Обновление завершено!');
        $this->info('👉 Запустите: sail artisan db:seed --class=DatabaseSeeder');
        
        return 0;
    }
    
    /**
     * Читает матрицу доступа из CSV
     */
    private function readAccessMatrix(): array
    {
        $content = File::get('docs/access_matrix.csv');
        $lines = explode("\n", trim($content));
        
        $matrix = [];
        $headers = str_getcsv(array_shift($lines));
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $row = str_getcsv($line);
            if (count($row) < 2) continue;
            
            $resource = $row[0];
            $model = strtolower($row[1]);
            $notes = end($row);
            
            $matrix[$resource] = [
                'model' => $model,
                'access' => [],
                'notes' => $notes,
            ];
            
            // Заполняем доступ для каждой роли
            $roles = array_slice($headers, 2, -1); // Пропускаем Resource, Model и notes
            foreach ($roles as $index => $role) {
                $accessIndex = $index + 2;
                $accessType = isset($row[$accessIndex]) ? trim($row[$accessIndex]) : '❌';
                $matrix[$resource]['access'][$role] = $accessType;
            }
        }
        
        $this->info("✅ Прочитано " . count($matrix) . " ресурсов из матрицы");
        return $matrix;
    }
    
    /**
     * Читает таблицу ограниченного доступа
     */
    private function readLimitedAccessTable(): array
    {
        $limitedAccess = [];
        
        if (File::exists('docs/limited_access.csv')) {
            $content = File::get('docs/limited_access.csv');
            $lines = explode("\n", trim($content));
            
            array_shift($lines); // Пропускаем заголовок
            
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                $row = str_getcsv($line);
                if (count($row) < 3) continue;
                
                $resource = $row[0];
                $role = $row[1];
                $permissions = explode(',', $row[2]);
                
                if (!isset($limitedAccess[$resource])) {
                    $limitedAccess[$resource] = [];
                }
                
                $limitedAccess[$resource][$role] = array_map('trim', $permissions);
            }
            
            $this->info("✅ Прочитана таблица ограниченного доступа");
        } else {
            $this->warn("⚠️  Таблица ограниченного доступа не найдена: docs/limited_access.csv");
            $this->info("💡 Создайте файл для кастомных разрешений");
        }
        
        return $limitedAccess;
    }
    
    /**
     * Генерирует разрешения на основе матрицы
     */
    private function generatePermissions(array $matrix, array $limitedAccess): void
    {
        $allPermissions = [];
        
        foreach ($matrix as $resource => $data) {
            $model = $data['model'];
            $group = $this->permissionGroups[$model] ?? $model;
            
            // Для каждого типа доступа создаем соответствующие разрешения
            foreach ($data['access'] as $role => $accessType) {
                if ($accessType === '❌') {
                    continue; // Нет разрешений
                }
                
                // Базовые CRUD разрешения
                if (isset($this->accessTypeToPermissions[$accessType])) {
                    foreach ($this->accessTypeToPermissions[$accessType] as $action) {
                        $permissionName = "{$action}_{$model}";
                        $description = $this->getPermissionDescription($action, $model);
                        
                        $allPermissions[] = [
                            'name' => $permissionName,
                            'group' => $group,
                            'description' => $description,
                        ];
                    }
                }
                
                // Кастомные разрешения для ограниченного доступа
                if ($accessType === '🔐' && isset($limitedAccess[$resource][$role])) {
                    foreach ($limitedAccess[$resource][$role] as $permissionName) {
                        $allPermissions[] = [
                            'name' => $permissionName,
                            'group' => $group,
                            'description' => $this->getCustomPermissionDescription($permissionName),
                        ];
                    }
                }
            }
        }
        
        // Удаляем дубликаты
        $uniquePermissions = [];
        foreach ($allPermissions as $perm) {
            $uniquePermissions[$perm['name']] = $perm;
        }
        
        // Создаем или обновляем разрешения
        foreach ($uniquePermissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                [
                    'guard_name' => 'web',
                    'group' => $perm['group'],
                    'description' => $perm['description']
                ]
            );
        }
        
        $this->info("✅ Создано/обновлено " . count($uniquePermissions) . " разрешений");
    }
    
    /**
     * Обновляет RoleSeeder на основе матрицы
     */
    private function updateRoleSeeder(array $matrix, array $limitedAccess): void
    {
        $rolePermissions = [];
        $roles = ['admin', 'initiator', 'dispatcher', 'executor', 'contractor_admin', 
                 'contractor_dispatcher', 'contractor_executor', 'hr', 'manager', 'trainee', 'viewer'];
        
        // Инициализируем массив для каждой роли
        foreach ($roles as $role) {
            $rolePermissions[$role] = [];
        }
        
        // Собираем разрешения для каждой роли
        foreach ($matrix as $resource => $data) {
            $model = $data['model'];
            
            foreach ($data['access'] as $role => $accessType) {
                if ($accessType === '❌') {
                    continue;
                }
                
                // Базовые CRUD
                if (isset($this->accessTypeToPermissions[$accessType])) {
                    foreach ($this->accessTypeToPermissions[$accessType] as $action) {
                        $permissionName = "{$action}_{$model}";
                        $rolePermissions[$role][] = $permissionName;
                    }
                }
                
                // Кастомные разрешения
                if ($accessType === '🔐' && isset($limitedAccess[$resource][$role])) {
                    foreach ($limitedAccess[$resource][$role] as $permissionName) {
                        $rolePermissions[$role][] = $permissionName;
                    }
                }
            }
        }
        
        // Добавляем системные разрешения
        $rolePermissions['admin'][] = 'all';
        $rolePermissions['viewer'][] = 'view_reports';
        
        // Удаляем дубликаты
        foreach ($rolePermissions as $role => $permissions) {
            $rolePermissions[$role] = array_unique($permissions);
        }
        
        // Генерируем PHP код для массива
        $phpCode = $this->generateRolePermissionsArray($rolePermissions);
        
        // Сохраняем в файл для ручного копирования
        File::put('docs/generated_role_permissions.php', $phpCode);
        
        $this->info("✅ Сгенерирован массив разрешений для RoleSeeder");
        $this->info("💡 Скопируйте содержимое из docs/generated_role_permissions.php в RoleSeeder::\$rolePermissions");
    }
    
    /**
     * Генерирует описание разрешения
     */
    private function getPermissionDescription(string $action, string $model): string
    {
        $actionNames = [
            'view_any' => 'Просмотр всех',
            'view' => 'Просмотр',
            'create' => 'Создание',
            'update' => 'Редактирование',
            'delete' => 'Удаление',
            'restore' => 'Восстановление',
            'force_delete' => 'Полное удаление',
        ];
        
        $modelNames = $this->permissionGroups;
        
        $actionRu = $actionNames[$action] ?? $action;
        $modelRu = $modelNames[$model] ?? $model;
        
        return "{$actionRu} {$modelRu}";
    }
    
    /**
     * Генерирует описание кастомного разрешения
     */
    private function getCustomPermissionDescription(string $permissionName): string
    {
        $descriptions = [
            'confirm_assignment' => 'Подтверждение назначения',
            'reject_assignment' => 'Отклонение назначения',
            'view_own_assignment' => 'Просмотр своих назначений',
            'publish_workrequest' => 'Публикация заявки',
            'take_workrequest' => 'Взятие заявки в работу',
            'start_shift' => 'Начало смены',
            'end_shift' => 'Завершение смены',
            'view_reports' => 'Просмотр отчетов',
        ];
        
        return $descriptions[$permissionName] ?? $permissionName;
    }
    
    /**
     * Генерирует PHP код массива разрешений для ролей
     */
    private function generateRolePermissionsArray(array $rolePermissions): string
    {
        $lines = [];
        $lines[] = 'private array $rolePermissions = [';
        
        foreach ($rolePermissions as $role => $permissions) {
            if ($role === 'admin') {
                $lines[] = "    '{$role}' => 'all', // Все разрешения";
                continue;
            }
            
            if (empty($permissions)) {
                $lines[] = "    '{$role}' => [],";
                continue;
            }
            
            $lines[] = "    '{$role}' => [";
            foreach ($permissions as $permission) {
                $lines[] = "        '{$permission}',";
            }
            $lines[] = "    ],";
        }
        
        $lines[] = '];';
        
        return implode("\n", $lines);
    }
}


<?php
// app/Console/Commands/OptimizeNavigation.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OptimizeNavigation extends Command
{
    protected $signature = 'navigation:optimize {--dry-run : Показать изменения без сохранения}';
    protected $description = 'Оптимизация групп навигации Filament';

    // Маппинг старых групп на новые
    protected array $groupMappings = [
        // Новая группа => Старые группы
        '👥 Управление персоналом' => [
            'Управление персоналом',
            'Массовый персонал', 
            'Организация',
        ],
        '🎯 Подбор персонала' => [
            'Подбор персонала',
            '👥 Рекрутинг',
        ],
        '🏗️ Проекты и геолокации' => [
            'Управление проектами',
            'Геолокации и фото',
        ],
        '💰 Финансы' => [
            'Финансы',
        ],
        '📊 Учет работ' => [
            'Учет работ',
            'Заявки на работы',
        ],
        '⚙️ Справочники и настройки' => [
            'Справочники',
            'Контент и медиа',
            'Управление доступом',
        ],
        '👑 Система' => [
            'Система',
        ],
    ];

    // Порядок сортировки для новых групп
    protected array $groupSortOrder = [
        '👥 Управление персоналом' => 10,
        '🎯 Подбор персонала' => 20,
        '📊 Учет работ' => 30,
        '💰 Финансы' => 40,
        '🏗️ Проекты и геолокации' => 50,
        '⚙️ Справочники и настройки' => 60,
        '👑 Система' => 70,
    ];

    public function handle()
    {
        $this->info('🎯 ОПТИМИЗАЦИЯ ГРУПП НАВИГАЦИИ FILAMENT');
        $this->line('=========================================');
        
        $resourcesPath = app_path('Filament/Resources');
        $files = glob($resourcesPath . '/*Resource.php');
        
        $changes = [];
        $dryRun = $this->option('dry-run');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $originalContent = $content;
            
            // Ищем текущую группу
            preg_match('/protected static\s*\??string\s*\$navigationGroup\s*=\s*[\'"]([^\'"]+)[\'"];/', $content, $matches);
            
            if (!empty($matches[1])) {
                $currentGroup = $matches[1];
                $newGroup = $this->getNewGroup($currentGroup);
                
                if ($newGroup && $newGroup !== $currentGroup) {
                    // Находим и заменяем группу
                    $newContent = preg_replace(
                        '/protected static\s*\??string\s*\$navigationGroup\s*=\s*[\'"]([^\'"]+)[\'"];/',
                        'protected static ?string $navigationGroup = \'' . $newGroup . '\';',
                        $content
                    );
                    
                    // Обновляем сортировку
                    $newContent = $this->updateNavigationSort($newContent, $newGroup);
                    
                    if ($originalContent !== $newContent) {
                        $resourceName = basename($file, '.php');
                        $changes[] = [
                            'resource' => $resourceName,
                            'from' => $currentGroup,
                            'to' => $newGroup,
                        ];
                        
                        if (!$dryRun) {
                            file_put_contents($file, $newContent);
                        }
                    }
                }
            }
        }
        
        $this->newLine();
        
        if (empty($changes)) {
            $this->info('✅ Изменений не требуется. Группы уже оптимизированы.');
        } else {
            $this->info('📊 ПЛАНИРУЕМЫЕ ИЗМЕНЕНИЯ:');
            $this->table(['Ресурс', 'Старая группа', 'Новая группа'], $changes);
            
            if ($dryRun) {
                $this->warn('⚠️  Это тестовый прогон (dry-run). Для применения изменений запустите команду без --dry-run.');
            } else {
                $this->info('✅ Изменения применены успешно!');
            }
        }
        
        // Выводим статистику по новым группам
        $this->newLine();
        $this->info('🎯 НОВАЯ СТРУКТУРА ГРУПП:');
        
        $groupStats = [];
        foreach ($this->groupMappings as $newGroup => $oldGroups) {
            $groupStats[] = [
                'Группа' => $newGroup,
                'Порядок' => $this->groupSortOrder[$newGroup] ?? 100,
                'Старые группы' => implode(', ', $oldGroups),
            ];
        }
        
        $this->table(['Группа', 'Порядок', 'Объединяет группы'], $groupStats);
        
        return Command::SUCCESS;
    }
    
    protected function getNewGroup(string $currentGroup): ?string
    {
        foreach ($this->groupMappings as $newGroup => $oldGroups) {
            if (in_array($currentGroup, $oldGroups)) {
                return $newGroup;
            }
        }
        
        // Если группа не найдена в маппинге, оставляем как есть
        return $currentGroup;
    }
    
    protected function updateNavigationSort(string $content, string $group): string
    {
        $sortOrder = $this->groupSortOrder[$group] ?? 100;
        
        // Ищем существующий navigationSort
        if (preg_match('/protected static\s*\??int\s*\$navigationSort\s*=\s*(\d+);/', $content)) {
            // Заменяем существующий
            $content = preg_replace(
                '/protected static\s*\??int\s*\$navigationSort\s*=\s*\d+;/',
                'protected static ?int $navigationSort = ' . $sortOrder . ';',
                $content
            );
        } else {
            // Добавляем новый после navigationGroup
            $content = preg_replace(
                '/(protected static\s*\??string\s*\$navigationGroup\s*=\s*[\'"][^\'"]+[\'"];)/',
                "$1\n\n    protected static ?int \$navigationSort = $sortOrder;",
                $content
            );
        }
        
        return $content;
    }
}

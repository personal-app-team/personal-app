<?php
// app/Console/Commands/StandardizeIcons.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StandardizeIcons extends Command
{
    protected $signature = 'icons:standardize {--dry-run : Показать изменения без сохранения}';
    protected $description = 'Стандартизация иконок для групп навигации';

    // Маппинг групп на иконки Heroicons
    protected array $groupIcons = [
        '👥 Управление персоналом' => 'heroicon-o-users',
        '🎯 Подбор персонала' => 'heroicon-o-briefcase',
        '📊 Учет работ' => 'heroicon-o-clipboard-document-check',
        '💰 Финансы' => 'heroicon-o-currency-dollar',
        '🏗️ Проекты и геолокации' => 'heroicon-o-map',
        '⚙️ Справочники и настройки' => 'heroicon-o-cog-6-tooth',
        '👑 Система' => 'heroicon-o-shield-check',
    ];

    // Маппинг ресурсов на иконки (если нужно переопределить)
    protected array $resourceIcons = [
        'ActivityLogResource' => 'heroicon-o-clipboard-document-list',
        'UserResource' => 'heroicon-o-users',
        'VacancyResource' => 'heroicon-o-briefcase',
        'ShiftResource' => 'heroicon-o-clock',
        'WorkRequestResource' => 'heroicon-o-document-text',
        'AssignmentResource' => 'heroicon-o-user-plus',
        'ProjectResource' => 'heroicon-o-building-office',
        'CategoryResource' => 'heroicon-o-tag',
        'SpecialtyResource' => 'heroicon-o-wrench-screwdriver',
        'ContractorResource' => 'heroicon-o-building-office-2',
        'RoleResource' => 'heroicon-o-key',
        'PhotoResource' => 'heroicon-o-photo',
        'AddressResource' => 'heroicon-o-map-pin',
        'CompensationResource' => 'heroicon-o-banknotes',
        'ExpenseResource' => 'heroicon-o-credit-card',
    ];

    public function handle()
    {
        $this->info('🎨 СТАНДАРТИЗАЦИЯ ИКОНОК FILAMENT');
        $this->line('=================================');
        
        $resourcesPath = app_path('Filament/Resources');
        $files = glob($resourcesPath . '/*Resource.php');
        
        $changes = [];
        $dryRun = $this->option('dry-run');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $originalContent = $content;
            
            $resourceName = basename($file, '.php');
            
            // Определяем группу ресурса
            preg_match('/protected static\s*\??string\s*\$navigationGroup\s*=\s*[\'"]([^\'"]+)[\'"];/', $content, $groupMatches);
            $group = $groupMatches[1] ?? null;
            
            // Определяем иконку
            $icon = $this->getIconForResource($resourceName, $group);
            
            if ($icon) {
                // Ищем существующую иконку
                if (preg_match('/protected static\s*\??string\s*\$navigationIcon\s*=\s*[\'"]([^\'"]+)[\'"];/', $content)) {
                    // Заменяем существующую
                    $newContent = preg_replace(
                        '/protected static\s*\??string\s*\$navigationIcon\s*=\s*[\'"]([^\'"]+)[\'"];/',
                        'protected static ?string $navigationIcon = \'' . $icon . '\';',
                        $content
                    );
                } else {
                    // Добавляем новую после model или перед navigationGroup
                    if (preg_match('/(protected static\s*\??string\s*\$model\s*=\s*[^;]+;)/', $content, $modelMatch)) {
                        $newContent = preg_replace(
                            '/(protected static\s*\??string\s*\$model\s*=\s*[^;]+;)/',
                            "$1\n\n    protected static ?string \$navigationIcon = '$icon';",
                            $content
                        );
                    }
                }
                
                if (isset($newContent) && $originalContent !== $newContent) {
                    $changes[] = [
                        'resource' => $resourceName,
                        'icon' => $icon,
                    ];
                    
                    if (!$dryRun) {
                        file_put_contents($file, $newContent);
                    }
                }
            }
        }
        
        $this->newLine();
        
        if (empty($changes)) {
            $this->info('✅ Изменений не требуется. Иконки уже стандартизированы.');
        } else {
            $this->info('🎨 ИЗМЕНЕНИЯ ИКОНОК:');
            $this->table(['Ресурс', 'Новая иконка'], $changes);
            
            if ($dryRun) {
                $this->warn('⚠️  Это тестовый прогон (dry-run). Для применения изменений запустите команду без --dry-run.');
            } else {
                $this->info('✅ Иконки стандартизированы успешно!');
            }
        }
        
        return Command::SUCCESS;
    }
    
    protected function getIconForResource(string $resourceName, ?string $group): ?string
    {
        // Сначала проверяем специфичные иконки для ресурсов
        if (isset($this->resourceIcons[$resourceName])) {
            return $this->resourceIcons[$resourceName];
        }
        
        // Затем иконки по группам
        if ($group && isset($this->groupIcons[$group])) {
            return $this->groupIcons[$group];
        }
        
        return null;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AnalyzeNavigation extends Command
{
    protected $signature = 'navigation:analyze';
    protected $description = 'Анализ структуры навигации Filament';

    public function handle()
    {
        $this->info('🎯 АНАЛИЗ СТРУКТУРЫ НАВИГАЦИИ FILAMENT');
        $this->line('=====================================');
        
        $resourcesPath = app_path('Filament/Resources');
        $files = glob($resourcesPath . '/*Resource.php');
        
        $groups = [];
        $total = 0;
        
        $this->newLine();
        $this->info('📊 ТЕКУЩИЕ ГРУППЫ И РЕСУРСЫ:');
        $this->line('------------------------------');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            // Ищем navigationGroup (более гибкое выражение)
            preg_match('/protected static\s*\??string\s*\$navigationGroup\s*=\s*[\'"]([^\'"]+)[\'"];/', $content, $groupMatches);
            $group = $groupMatches[1] ?? '❌ Без группы';
            
            // Ищем navigationLabel
            preg_match('/protected static\s*\??string\s*\$navigationLabel\s*=\s*[\'"]([^\'"]+)[\'"];/', $content, $labelMatches);
            if (empty($labelMatches)) {
                preg_match('/protected static\s*\??string\s*\$modelLabel\s*=\s*[\'"]([^\'"]+)[\'"];/', $content, $labelMatches);
            }
            $label = $labelMatches[1] ?? basename($file, 'Resource.php');
            
            // Проверяем, скрыт ли ресурс
            $isHidden = Str::contains($content, '$shouldRegisterNavigation = false');
            $hiddenMark = $isHidden ? ' 👻' : '';
            
            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }
            
            $groups[$group][] = $label . $hiddenMark;
            $total++;
        }
        
        $this->line("Всего ресурсов: {$total}");
        $this->newLine();
        
        // Сортируем группы по количеству ресурсов
        uasort($groups, function($a, $b) {
            return count($b) <=> count($a);
        });
        
        foreach ($groups as $group => $resources) {
            $count = count($resources);
            $this->line("## {$group} ({$count} ресурсов)");
            foreach ($resources as $resource) {
                $this->line("  • {$resource}");
            }
            $this->newLine();
        }
        
        // Статистика
        $hiddenCount = 0;
        foreach ($groups as $group => $resources) {
            foreach ($resources as $resource) {
                if (Str::contains($resource, '👻')) {
                    $hiddenCount++;
                }
            }
        }
        
        $this->info('📈 СТАТИСТИКА:');
        $this->line("Всего ресурсов: {$total}");
        $this->line("Скрытых ресурсов: {$hiddenCount}");
        $this->line("Видимых ресурсов: " . ($total - $hiddenCount));
        $this->line("Групп навигации: " . count($groups));
        
        $this->newLine();
        $this->info('💡 РЕКОМЕНДАЦИИ ПО ГРУППИРОВКЕ:');
        $this->line('-------------------------------');
        $this->line('1. Стандартизировать иконки и названия групп');
        $this->line('2. Проверить ресурсы без группы');
        $this->line('3. Убедиться в логической группировке');
        $this->line('4. Оптимизировать порядок сортировки (navigationSort)');
        
        return Command::SUCCESS;
    }
}

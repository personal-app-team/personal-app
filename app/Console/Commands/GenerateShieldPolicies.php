<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class GenerateShieldPolicies extends Command
{
    protected $signature = 'shield:generate-correct';
    protected $description = 'Генерирует политики Shield в правильную папку с правильными namespace';

    public function handle(): void
    {
        $this->info('🛡️  Запуск генерации политик Shield...');

        // 1. Генерируем политики через Shield
        Artisan::call('shield:generate', ['--all' => true]);
        $this->info('✅ Политики сгенерированы через Shield');

        // 2. Исправляем пути и namespace
        $this->fixPoliciesPaths();
        
        $this->info('🎉 Генерация политик завершена!');
    }

    private function fixPoliciesPaths(): void
    {
        $incorrectPath = base_path('app/var/www/html/app/Policies');
        $correctPath = base_path('app/Policies');

        // Проверяем, есть ли политики в неправильной папке
        if (!File::exists($incorrectPath)) {
            $this->warn('⚠️  Политики не найдены в неправильной папке. Возможно, они уже в правильной.');
            return;
        }

        // Копируем файлы в правильную папку
        if (!File::exists($correctPath)) {
            File::makeDirectory($correctPath, 0755, true);
        }

        $files = File::files($incorrectPath);
        
        foreach ($files as $file) {
            $filename = $file->getFilename();
            $sourcePath = $file->getPathname();
            $destinationPath = $correctPath . '/' . $filename;
            
            // Читаем содержимое файла
            $content = File::get($sourcePath);
            
            // Исправляем namespace (убираем лишний App\)
            $content = str_replace(
                ['namespace App\\App\\Policies;', 'namespace App\App\Policies;'],
                'namespace App\\Policies;',
                $content
            );
            
            // Записываем исправленный файл в правильную папку
            File::put($destinationPath, $content);
            
            $this->line("✅ Исправлен: {$filename}");
        }

        // Удаляем неправильную папку
        File::deleteDirectory($incorrectPath);
        
        $this->info("📁 Политики перемещены в: {$correctPath}");
    }
}

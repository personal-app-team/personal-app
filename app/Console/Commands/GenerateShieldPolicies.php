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

        if (!File::exists($incorrectPath)) {
            $this->warn('⚠️  Политики не найдены в неправильной папке.');
            return;
        }

        // Создаем правильную папку
        if (!File::exists($correctPath)) {
            File::makeDirectory($correctPath, 0755, true);
        }

        $files = File::files($incorrectPath);
        
        foreach ($files as $file) {
            $filename = $file->getFilename();
            $sourcePath = $file->getPathname();
            $destinationPath = $correctPath . '/' . $filename;
            
            $content = File::get($sourcePath);
            $content = str_replace(
                ['namespace App\\App\\Policies;', 'namespace App\App\Policies;'],
                'namespace App\\Policies;',
                $content
            );
            
            File::put($destinationPath, $content);
            $this->line("✅ Исправлен: {$filename}");
        }

        // 🔧 УДАЛЯЕМ РЕКУРСИВНО ЧЕРЕЗ system call (для WSL/Docker)
        $this->deleteRecursive(base_path('app/var'));
        
        $this->info("📁 Политики перемещены в: {$correctPath}");
    }

    private function deleteRecursive(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        
        // Пробуем через File facade
        try {
            if (File::deleteDirectory($path)) {
                $this->info("🗑️  Удалено через File::deleteDirectory: {$path}");
                return;
            }
        } catch (\Exception $e) {
            $this->warn("File::deleteDirectory не сработал: " . $e->getMessage());
        }
        
        // Пробуем через system call (работает в WSL)
        $command = 'rm -rf "' . str_replace('"', '\"', $path) . '" 2>/dev/null';
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->info("🗑️  Удалено через system call: {$path}");
        } else {
            $this->error("❌ Не удалось удалить {$path}");
            $this->line("   Удалите вручную: rm -rf app/var");
        }
    }
}

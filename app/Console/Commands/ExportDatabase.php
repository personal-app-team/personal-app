<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ExportDatabase extends Command
{
    protected $signature = 'app:export-database {--name=}';
    protected $description = 'Export database to zip file for sharing';

    public function handle()
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        // Генерируем имя файла
        $filename = $this->option('name') ?? 'database_export_' . date('Y-m-d_His');
        $sqlFile = "{$filename}.sql";
        $zipFile = "{$filename}.zip";

        $this->info("🔄 Exporting database {$database}...");

        // Создаем папку если нет
        if (!Storage::disk('local')->exists('exports')) {
            Storage::disk('local')->makeDirectory('exports');
        }

        $sqlPath = Storage::disk('local')->path("exports/{$sqlFile}");

        try {
            // Создаем SQL dump с правильными параметрами
            $process = new Process([
                'docker-compose', 'exec', '-T', 'mysql',
                'mysqldump',
                '-u', $username,
                '-p' . $password,
                '--no-tablespaces',
                '--skip-lock-tables',
                '--force', // Игнорируем ошибки в views
                $database
            ]);
            
            $process->setTimeout(300); // 5 минут таймаут
            $process->mustRun();
            
            // Сохраняем результат
            Storage::disk('local')->put("exports/{$sqlFile}", $process->getOutput());
            
        } catch (ProcessFailedException $exception) {
            $this->error('❌ Failed to create database dump: ' . $exception->getMessage());
            
            // Пробуем альтернативный способ
            $this->warn('🔄 Trying alternative method...');
            return $this->tryAlternativeExport($database, $sqlFile, $zipFile, $filename);
        }

        return $this->createZipArchive($sqlFile, $zipFile, $filename);
    }

    private function tryAlternativeExport($database, $sqlFile, $zipFile, $filename)
    {
        try {
            // Альтернативный способ - через shell команду
            $command = "sail exec mysql mysqldump -u sail -psecret --no-tablespaces --skip-lock-tables --force {$database} > " . storage_path("app/exports/{$sqlFile}");
            
            shell_exec($command);
            
            if (!Storage::disk('local')->exists("exports/{$sqlFile}") || Storage::disk('local')->size("exports/{$sqlFile}") === 0) {
                throw new \Exception('Export file is empty or not created');
            }
            
            return $this->createZipArchive($sqlFile, $zipFile, $filename);
            
        } catch (\Exception $e) {
            $this->error('❌ Alternative method also failed: ' . $e->getMessage());
            $this->line('💡 Try exporting manually:');
            $this->line("sail exec mysql mysqldump -u sail -psecret --no-tablespaces --skip-lock-tables --force {$database} > export.sql");
            return 1;
        }
    }

    private function createZipArchive($sqlFile, $zipFile, $filename)
    {
        // Создаем ZIP архив
        $zip = new \ZipArchive();
        $zipPath = Storage::disk('local')->path("exports/{$zipFile}");
        
        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            $zip->addFile(Storage::disk('local')->path("exports/{$sqlFile}"), $sqlFile);
            $zip->close();
            
            // Удаляем временный SQL файл
            Storage::disk('local')->delete("exports/{$sqlFile}");
            
            $fullPath = Storage::disk('local')->path("exports/{$zipFile}");
            $fileSize = number_format(filesize($zipPath) / 1024 / 1024, 2);
            
            $this->info("✅ Database exported successfully!");
            $this->info("📁 File: {$fullPath}");
            $this->info("📦 Size: {$fileSize} MB");
            $this->line("");
            $this->info("📤 Upload to Google Drive and share the link with your colleague.");
            
            return 0;
        }

        $this->error('❌ Failed to create zip archive');
        return 1;
    }
}

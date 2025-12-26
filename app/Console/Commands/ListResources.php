<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class ListResources extends Command
{
    protected $signature = 'resources:list';
    protected $description = 'List all Filament resources with their models';

    public function handle()
    {
        $resourcesPath = app_path('Filament/Resources');
        $finder = new Finder();

        $this->info("📋 Список всех Filament Resources:");
        $this->info("========================================");

        $resources = [];

        foreach ($finder->files()->in($resourcesPath)->name('*Resource.php') as $file) {
            $className = 'App\\Filament\\Resources\\' . $file->getBasename('.php');

            if (class_exists($className)) {
                try {
                    $model = $className::getModel();
                    $modelName = class_basename($model);
                } catch (\Exception $e) {
                    $modelName = 'Не указана';
                }

                // Получаем navigationGroup через рефлексию
                $navigationGroup = $this->getNavigationGroup($className);
                
                $resources[] = [
                    'Resource' => $file->getBasename('.php'),
                    'Model' => $modelName,
                    'Navigation Group' => $navigationGroup,
                ];
            }
        }

        $this->table(
            ['Resource', 'Model', 'Navigation Group'],
            $resources
        );

        $this->info("\n🎯 Всего ресурсов: " . count($resources));

        // Также проверим какие модели без ресурсов
        $this->info("\n🔍 Модели без Filament Resources:");
        $this->info("========================================");

        $modelFiles = glob(app_path('Models/*.php'));
        $modelsWithoutResources = [];

        foreach ($modelFiles as $modelFile) {
            $modelName = basename($modelFile, '.php');
            $resourceFile = app_path("Filament/Resources/{$modelName}Resource.php");

            if (!file_exists($resourceFile)) {
                $modelsWithoutResources[] = $modelName;
            }
        }

        if (count($modelsWithoutResources) > 0) {
            foreach ($modelsWithoutResources as $model) {
                $this->line("❌ {$model}");
            }
            $this->info("Всего моделей без ресурсов: " . count($modelsWithoutResources));
        } else {
            $this->info("✅ У всех моделей есть ресурсы!");
        }
    }
    
    private function getNavigationGroup(string $className): string
    {
        try {
            $reflection = new \ReflectionClass($className);
            $property = $reflection->getProperty('navigationGroup');
            $property->setAccessible(true);
            return $property->getValue() ?: '—';
        } catch (\Exception $e) {
            return '—';
        }
    }
}

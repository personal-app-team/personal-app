<?php

namespace App\Console\Commands;

use App\Models\VisitedLocation;
use App\Models\Photo;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class DebugPolymorphicRelations extends Command
{
    protected $signature = 'debug:polymorphic
                            {model? : Model to check (VisitedLocation or Photo)}
                            {--fix : Fix broken records}
                            {--delete : Delete broken records}';
    
    protected $description = 'Debug and fix polymorphic relations issues';

    private array $allowedTypes = [
        'App\\Models\\Shift',
        'App\\Models\\MassPersonnelReport',
        'App\\Models\\Expense',
        'App\\Models\\ContractorWorker',
    ];

    public function handle()
    {
        $model = $this->argument('model') ?? 'VisitedLocation';
        
        if (!in_array($model, ['VisitedLocation', 'Photo'])) {
            $this->error("Invalid model. Use: VisitedLocation or Photo");
            return 1;
        }

        $this->info("🔍 Checking {$model} polymorphic relations...");
        
        if ($model === 'VisitedLocation') {
            $this->checkVisitedLocations();
        } else {
            $this->checkPhotos();
        }

        return 0;
    }

    private function checkVisitedLocations(): void
    {
        $locations = VisitedLocation::all();
        $total = $locations->count();
        $broken = 0;
        
        $this->table(['ID', 'Type', 'Type ID', 'Status', 'Address'], []);

        foreach ($locations as $location) {
            $status = $this->checkRecord($location, 'visitable_type', 'visitable_id', 'visitable');
            
            if ($status !== '✅ OK') {
                $broken++;
                $this->table([], [[
                    $location->id,
                    $location->visitable_type,
                    $location->visitable_id,
                    $status,
                    substr($location->address ?? '', 0, 30) . '...'
                ]]);

                if ($this->option('fix')) {
                    $this->fixVisitedLocation($location);
                } elseif ($this->option('delete')) {
                    $location->delete();
                    $this->warn("  🗑️ Deleted VisitedLocation ID: {$location->id}");
                }
            }
        }

        $this->info("📊 Total: {$total}, Broken: {$broken}, OK: " . ($total - $broken));
    }

    private function checkPhotos(): void
    {
        $photos = Photo::all();
        $total = $photos->count();
        $broken = 0;
        
        $this->table(['ID', 'Type', 'Type ID', 'Status', 'Path'], []);

        foreach ($photos as $photo) {
            $status = $this->checkRecord($photo, 'imageable_type', 'imageable_id', 'imageable');
            
            if ($status !== '✅ OK') {
                $broken++;
                $this->table([], [[
                    $photo->id,
                    $photo->imageable_type,
                    $photo->imageable_id,
                    $status,
                    substr($photo->path ?? '', 0, 30) . '...'
                ]]);

                if ($this->option('delete')) {
                    $photo->delete();
                    $this->warn("  🗑️ Deleted Photo ID: {$photo->id}");
                }
            }
        }

        $this->info("📊 Total: {$total}, Broken: {$broken}, OK: " . ($total - $broken));
    }

    private function checkRecord(Model $record, string $typeField, string $idField, string $relation): string
    {
        $type = $record->{$typeField};
        $id = $record->{$idField};

        // Проверка 1: Тип NULL
        if (empty($type)) {
            return '⚠️ NULL type';
        }

        // Проверка 2: Класс не существует
        if (!class_exists($type)) {
            return '❌ Class not exists';
        }

        // Проверка 3: Тип не в разрешенных
        if (!in_array($type, $this->allowedTypes)) {
            return '⚠️ Invalid type';
        }

        // Проверка 4: Связанная модель не найдена
        try {
            $related = $record->{$relation};
            if (!$related) {
                return '🔗 Relation broken';
            }
        } catch (\Exception $e) {
            return '🚫 Relation error: ' . $e->getMessage();
        }

        return '✅ OK';
    }

    private function fixVisitedLocation(VisitedLocation $location): void
    {
        $type = $location->visitable_type;
        
        // Если тип не существует, пробуем найти подходящую замену
        if (!class_exists($type)) {
            $this->warn("  🔧 Fixing type for VisitedLocation ID: {$location->id}");
            
            // Пробуем найти WorkRequest, чтобы связать с ContractorWorker
            if ($location->address && str_contains($location->address, 'масс')) {
                // Пытаемся найти подходящий отчет массового персонала
                $report = \App\Models\MassPersonnelReport::first();
                if ($report) {
                    $location->visitable_type = 'App\\Models\\MassPersonnelReport';
                    $location->visitable_id = $report->id;
                    $location->save();
                    $this->info("  ✅ Fixed: linked to MassPersonnelReport #{$report->id}");
                    return;
                }
            }
            
            // Если не нашли, помечаем как архивную запись
            $location->visitable_type = 'App\\Models\\Shift';
            $location->visitable_id = 0;
            $location->notes = (empty($location->notes) ? '' : $location->notes . "\n") . 
                              "[AUTO-FIXED] Original type was: {$type}";
            $location->save();
            $this->info("  ✅ Fixed: marked as archived Shift");
        }
    }
}

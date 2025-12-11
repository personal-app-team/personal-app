<?php

namespace App\Console\Commands;

use App\Models\VisitedLocation;
use Illuminate\Console\Command;

class CleanupOrphanedVisitedLocations extends Command
{
    protected $signature = 'cleanup:orphaned-visited-locations';
    protected $description = 'Удалить посещенные локации с несуществующими полиморфными связями';

    public function handle()
    {
        $locations = VisitedLocation::all();
        $deletedCount = 0;
        
        foreach ($locations as $location) {
            if (!$location->visitable) {
                $this->info("Удаление локации #{$location->id} (тип: {$location->visitable_type}, ID: {$location->visitable_id})");
                $location->delete();
                $deletedCount++;
            }
        }
        
        $this->info("✅ Удалено {$deletedCount} битых записей посещенных локаций");
        
        return 0;
    }
}

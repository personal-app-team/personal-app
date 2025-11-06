<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DropAllViews extends Command
{
    protected $signature = 'db:drop-views';
    protected $description = 'Drop all database views';

    public function handle()
    {
        $views = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = ?", [config('database.connections.mysql.database')]);
        
        if (empty($views)) {
            $this->info('✅ No views found in database.');
            return 0;
        }

        $this->info('Found ' . count($views) . ' views to drop:');
        
        foreach ($views as $view) {
            $this->line('- ' . $view->TABLE_NAME);
        }

        if ($this->confirm('Do you want to drop all these views?')) {
            foreach ($views as $view) {
                try {
                    DB::statement("DROP VIEW IF EXISTS `{$view->TABLE_NAME}`");
                    $this->info("✅ Dropped: {$view->TABLE_NAME}");
                } catch (\Exception $e) {
                    $this->error("❌ Failed to drop {$view->TABLE_NAME}: " . $e->getMessage());
                }
            }
            
            $this->info('🎉 All views dropped successfully!');
        }

        return 0;
    }
}

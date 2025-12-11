<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        echo "🔧 Восстановление структуры visited_locations...\n";

        if (Schema::hasTable('visited_locations')) {
            // Добавляем все поля, которые должны быть
            $this->addColumnIfNotExists('visited_locations', 'visitable_id', function (Blueprint $table) {
                $table->unsignedBigInteger('visitable_id')->nullable()->after('id');
            });
            
            $this->addColumnIfNotExists('visited_locations', 'visitable_type', function (Blueprint $table) {
                $table->string('visitable_type', 255)->nullable()->after('visitable_id');
            });
            
            $this->addColumnIfNotExists('visited_locations', 'address', function (Blueprint $table) {
                $table->string('address', 1000)->nullable()->after('visitable_type');
            });
            
            $this->addColumnIfNotExists('visited_locations', 'latitude', function (Blueprint $table) {
                $table->decimal('latitude', 10, 8)->nullable()->after('address');
            });
            
            $this->addColumnIfNotExists('visited_locations', 'longitude', function (Blueprint $table) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            });
            
            $this->addColumnIfNotExists('visited_locations', 'started_at', function (Blueprint $table) {
                $table->timestamp('started_at')->nullable()->after('longitude');
            });
            
            $this->addColumnIfNotExists('visited_locations', 'ended_at', function (Blueprint $table) {
                $table->timestamp('ended_at')->nullable()->after('started_at');
            });
            
            $this->addColumnIfNotExists('visited_locations', 'duration_minutes', function (Blueprint $table) {
                $table->integer('duration_minutes')->default(0)->after('ended_at');
            });
            
            $this->addColumnIfNotExists('visited_locations', 'notes', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('duration_minutes');
            });
            
            $this->addColumnIfNotExists('visited_locations', 'workers_count', function (Blueprint $table) {
                $table->integer('workers_count')->nullable()->after('duration_minutes');
            });

            // Добавляем индекс для полиморфной связи
            $indexExists = DB::select("
                SELECT 1 FROM information_schema.statistics 
                WHERE table_schema = DATABASE() 
                AND table_name = 'visited_locations' 
                AND index_name = 'visited_locations_visitable_type_visitable_id_index'
            ");
            
            if (empty($indexExists)) {
                Schema::table('visited_locations', function (Blueprint $table) {
                    $table->index(['visitable_type', 'visitable_id'], 'visited_locations_visitable_type_visitable_id_index');
                });
                echo "   ✅ Добавлен индекс для полиморфной связи\n";
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('visited_locations')) {
            // Удаляем индекс
            Schema::table('visited_locations', function (Blueprint $table) {
                $table->dropIndex(['visitable_type', 'visitable_id']);
            });
            
            // Удаляем добавленные поля
            $columnsToRemove = [
                'visitable_id', 'visitable_type', 'address', 'latitude', 'longitude',
                'started_at', 'ended_at', 'duration_minutes', 'notes', 'workers_count'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('visited_locations', $column)) {
                    Schema::table('visited_locations', function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        }
    }
    
    private function addColumnIfNotExists(string $table, string $column, callable $callback): void
    {
        if (!Schema::hasColumn($table, $column)) {
            Schema::table($table, $callback);
            echo "   ✅ Добавлено поле: {$column}\n";
        } else {
            echo "   ℹ️ Поле уже существует: {$column}\n";
        }
    }
};

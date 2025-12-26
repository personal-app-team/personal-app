<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Если таблица пустая (только базовые поля), добавляем необходимые колонки
        Schema::table('visited_locations', function (Blueprint $table) {
            // Добавляем user_id для связи с пользователем
            if (!Schema::hasColumn('visited_locations', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->onDelete('set null')
                    ->comment('Пользователь, который посетил локацию');
            }

            // Добавляем visitable_type для полиморфной связи
            if (!Schema::hasColumn('visited_locations', 'visitable_type')) {
                $table->string('visitable_type')
                    ->nullable()
                    ->after('user_id')
                    ->comment('Тип связанной модели (Shift, MassPersonnelReport и т.д.)');
            }

            // Добавляем visitable_id для полиморфной связи
            if (!Schema::hasColumn('visited_locations', 'visitable_id')) {
                $table->unsignedBigInteger('visitable_id')
                    ->nullable()
                    ->after('visitable_type')
                    ->comment('ID связанной модели');
            }

            // Добавляем остальные поля в правильном порядке
            $columns = [
                'address' => ['type' => 'string', 'after' => 'visitable_id', 'nullable' => false],
                'latitude' => ['type' => 'decimal', 'after' => 'address', 'nullable' => true, 'precision' => 10, 'scale' => 8],
                'longitude' => ['type' => 'decimal', 'after' => 'latitude', 'nullable' => true, 'precision' => 11, 'scale' => 8],
                'started_at' => ['type' => 'timestamp', 'after' => 'longitude', 'nullable' => true],
                'ended_at' => ['type' => 'timestamp', 'after' => 'started_at', 'nullable' => true],
                'duration_minutes' => ['type' => 'integer', 'after' => 'ended_at', 'default' => 0],
                'notes' => ['type' => 'text', 'after' => 'duration_minutes', 'nullable' => true],
            ];

            foreach ($columns as $column => $config) {
                if (!Schema::hasColumn('visited_locations', $column)) {
                    if ($config['type'] === 'decimal') {
                        $table->decimal($column, $config['precision'], $config['scale'])
                            ->nullable($config['nullable'] ?? false)
                            ->after($config['after']);
                    } elseif ($config['type'] === 'string') {
                        $table->string($column)
                            ->nullable($config['nullable'] ?? false)
                            ->after($config['after']);
                    } elseif ($config['type'] === 'timestamp') {
                        $table->timestamp($column)
                            ->nullable($config['nullable'] ?? false)
                            ->after($config['after']);
                    } elseif ($config['type'] === 'integer') {
                        $table->integer($column)
                            ->default($config['default'] ?? null)
                            ->after($config['after']);
                    } elseif ($config['type'] === 'text') {
                        $table->text($column)
                            ->nullable($config['nullable'] ?? false)
                            ->after($config['after']);
                    }
                }
            }

            // Добавляем индексы
            $table->index(['visitable_type', 'visitable_id']);
            $table->index('user_id');
            $table->index('started_at');
        });
    }

    public function down()
    {
        // Удаляем только добавленные колонки (кроме базовых)
        Schema::table('visited_locations', function (Blueprint $table) {
            $columns = [
                'notes',
                'duration_minutes', 
                'ended_at',
                'started_at',
                'longitude',
                'latitude',
                'address',
                'visitable_id',
                'visitable_type',
                'user_id'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('visited_locations', $column)) {
                    if ($column === 'user_id') {
                        $table->dropForeign(['user_id']);
                    }
                    $table->dropColumn($column);
                }
            }

            // Удаляем индексы
            $table->dropIndex(['visitable_type', 'visitable_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['started_at']);
        });
    }
};

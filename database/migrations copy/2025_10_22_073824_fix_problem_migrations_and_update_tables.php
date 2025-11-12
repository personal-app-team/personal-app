<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Исправляем users - убираем лишние поля если они есть
        if (Schema::hasColumn('users', 'is_contractor')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_contractor');
            });
        }
        
        if (Schema::hasColumn('users', 'is_always_brigadier')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_always_brigadier');
            });
        }

        // 2. Добавляем поля в contractors если их нет
        if (!Schema::hasColumn('contractors', 'user_id')) {
            Schema::table('contractors', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            });
        }

        if (!Schema::hasColumn('contractors', 'address')) {
            Schema::table('contractors', function (Blueprint $table) {
                $table->string('address')->nullable();
            });
        }

        if (!Schema::hasColumn('contractors', 'inn')) {
            Schema::table('contractors', function (Blueprint $table) {
                $table->string('inn', 12)->nullable();
            });
        }

        if (!Schema::hasColumn('contractors', 'bank_details')) {
            Schema::table('contractors', function (Blueprint $table) {
                $table->text('bank_details')->nullable();
            });
        }

        if (!Schema::hasColumn('contractors', 'notes')) {
            Schema::table('contractors', function (Blueprint $table) {
                $table->text('notes')->nullable();
            });
        }

        // 3. Добавляем selected_payer_company в work_requests если нет
        if (!Schema::hasColumn('work_requests', 'selected_payer_company')) {
            Schema::table('work_requests', function (Blueprint $table) {
                // Добавляем после существующего поля work_type_id
                $table->string('selected_payer_company')->nullable()->after('work_type_id');
            });
        }
    }

    public function down()
    {
        // При откате восстанавливаем структуру (опционально)
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_contractor')->default(false);
            $table->boolean('is_always_brigadier')->default(false);
        });

        Schema::table('contractors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'address', 'inn', 'bank_details', 'notes']);
        });

        Schema::table('work_requests', function (Blueprint $table) {
            $table->dropColumn('selected_payer_company');
        });
    }
};

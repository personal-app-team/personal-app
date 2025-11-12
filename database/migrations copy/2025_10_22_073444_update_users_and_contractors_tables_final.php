<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Обновляем users - убираем лишние поля
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_contractor')) {
                $table->dropColumn('is_contractor');
            }
            if (Schema::hasColumn('users', 'is_always_brigadier')) {
                $table->dropColumn('is_always_brigadier');
            }
        });

        // 2. Обновляем contractors - добавляем недостающие поля
        Schema::table('contractors', function (Blueprint $table) {
            if (!Schema::hasColumn('contractors', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('contractors', 'address')) {
                $table->string('address')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'inn')) {
                $table->string('inn', 12)->nullable();
            }
            if (!Schema::hasColumn('contractors', 'bank_details')) {
                $table->text('bank_details')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down()
    {
        // 1. Восстанавливаем users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_contractor')->default(false);
            $table->boolean('is_always_brigadier')->default(false);
        });

        // 2. Удаляем поля из contractors
        Schema::table('contractors', function (Blueprint $table) {
            if (Schema::hasColumn('contractors', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            $table->dropColumn(['address', 'inn', 'bank_details', 'notes']);
        });
    }
};

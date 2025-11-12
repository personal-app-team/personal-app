<?php
// database/migrations/2025_10_30_xxxxxx_sync_work_request_statuses_enums.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\WorkRequest;

return new class extends Migration
{
    public function up()
    {
        // Изменяем enum на text чтобы вместить все статусы
        Schema::table('work_request_statuses', function (Blueprint $table) {
            $table->text('status')->change();
        });
    }

    public function down()
    {
        // Возвращаем обратно к старому enum (приблизительно)
        Schema::table('work_request_statuses', function (Blueprint $table) {
            $table->enum('status', [
                'published','in_work','closed','shifts_not_opened','in_progress','shifts_not_closed','completed'
            ])->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('shift_segments')) {
            Schema::drop('shift_segments');
        }
    }

    public function down()
    {
        if (!Schema::hasTable('shift_segments')) {
            Schema::create('shift_segments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shift_id')->constrained()->onDelete('cascade');
                $table->foreignId('specialty_id')->nullable()->constrained();
                $table->foreignId('work_type_id')->nullable()->constrained();
                $table->integer('minutes')->default(0);
                $table->decimal('hourly_rate_snapshot', 8, 2)->default(0);
                $table->decimal('amount', 10, 2)->default(0);
                $table->timestamps();
            });
        }
    }
};

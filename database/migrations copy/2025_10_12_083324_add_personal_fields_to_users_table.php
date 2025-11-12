<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('name');
            $table->string('patronymic')->nullable()->after('surname');
            $table->string('telegram_id')->nullable()->after('phone');
            $table->boolean('is_always_brigadier')->default(false)->after('is_contractor');
            
            $table->index(['surname', 'name']);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['surname', 'name']);
            $table->dropColumn(['surname', 'patronymic', 'telegram_id', 'is_always_brigadier']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purpose_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('purpose_templates', 'description')) {
                $table->text('description')->nullable()->after('name')
                      ->comment('Описание шаблона назначения');
            }
        });
    }

    public function down()
    {
        Schema::table('purpose_templates', function (Blueprint $table) {
            if (Schema::hasColumn('purpose_templates', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
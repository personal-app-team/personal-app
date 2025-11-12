<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('compensations', function (Blueprint $table) {
            $table->id();
            $table->morphs('compensatable'); // Полиморфная связь: Shift или MassPersonnelReport
            $table->text('description')->comment('Описание компенсации');
            $table->decimal('requested_amount', 10, 2)->default(0)->comment('Запрошенная сумма');
            $table->decimal('approved_amount', 10, 2)->default(0)->comment('Утвержденная сумма');
            $table->string('status')->default('pending')
                  ->comment('pending, approved, rejected');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('approval_notes')->nullable()->comment('Комментарии при утверждении');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index(['compensatable_type', 'compensatable_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('compensations');
    }
};

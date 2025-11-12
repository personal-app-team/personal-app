<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('work_request_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_request_id')->constrained()->onDelete('cascade');
            $table->enum('status', [
                'published', 'in_work', 'closed', 'shifts_not_opened', 
                'in_progress', 'shifts_not_closed', 'completed'
            ]);
            $table->timestamp('changed_at');
            $table->foreignId('changed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['work_request_id', 'changed_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('work_request_statuses');
    }
};

// database/migrations/xxxx_xx_xx_xxxxxx_create_project_assignments_table.php  
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->string('assignment_name');
            $table->string('payer_company');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assignments');
    }
};

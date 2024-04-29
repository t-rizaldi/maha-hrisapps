<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('department_id')->nullable()->index();
            $table->string('sub_dept')->nullable();
            $table->integer('role')->default(0)->comment('0: staff, 1: spv, 2: manager, 3: gm, 4: director, 5: commisioner');
            $table->boolean('is_daily')->default(false);
            $table->tinyInteger('daily_level')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_titles');
    }
};

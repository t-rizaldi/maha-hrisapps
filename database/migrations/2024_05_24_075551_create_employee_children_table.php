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
        Schema::create('employee_children', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->index();
            $table->string('child_name')->nullable();
            $table->string('child_gender')->nullable();
            $table->integer('child_age')->nullable();
            $table->string('child_last_education')->nullable();
            $table->string('child_last_job_title')->nullable();
            $table->string('child_last_job_company')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_children');
    }
};

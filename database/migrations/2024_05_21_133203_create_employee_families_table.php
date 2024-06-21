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
        Schema::create('employee_families', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->index();
            $table->string('father_name')->nullable();
            $table->integer('father_status')->nullable();
            $table->integer('father_age')->nullable();
            $table->string('father_last_education')->nullable();
            $table->string('father_last_job_title')->nullable();
            $table->string('father_last_job_company')->nullable();
            $table->string('mother_name')->nullable();
            $table->integer('mother_status')->nullable();
            $table->integer('mother_age')->nullable();
            $table->string('mother_last_education')->nullable();
            $table->string('mother_last_job_title')->nullable();
            $table->string('mother_last_job_company')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('couple_name')->nullable();
            $table->integer('couple_age')->nullable();
            $table->string('couple_last_education')->nullable();
            $table->string('couple_last_job_title')->nullable();
            $table->string('couple_last_job_company')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_families');
    }
};

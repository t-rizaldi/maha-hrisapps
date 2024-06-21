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
        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->index();
            $table->string('last_education')->nullable();
            $table->string('primary_school')->nullable();
            $table->year('ps_start_year')->nullable();
            $table->year('ps_end_year')->nullable();
            $table->string('ps_certificate')->nullable();
            $table->string('ps_gpa')->nullable();
            $table->string('junior_high_school')->nullable();
            $table->year('jhs_start_year')->nullable();
            $table->year('jhs_end_year')->nullable();
            $table->string('jhs_certificate')->nullable();
            $table->string('jhs_gpa')->nullable();
            $table->string('senior_high_school')->nullable();
            $table->year('shs_start_year')->nullable();
            $table->year('shs_end_year')->nullable();
            $table->string('shs_certificate')->nullable();
            $table->string('shs_gpa')->nullable();
            $table->string('bachelor_university')->nullable();
            $table->string('bachelor_major')->nullable();
            $table->string('bachelor_start_year')->nullable();
            $table->string('bachelor_end_year')->nullable();
            $table->string('bachelor_certificate')->nullable();
            $table->string('bachelor_gpa')->nullable();
            $table->string('bachelor_degree')->nullable();
            $table->string('master_university')->nullable();
            $table->string('master_major')->nullable();
            $table->string('master_start_year')->nullable();
            $table->string('master_end_year')->nullable();
            $table->string('master_certificate')->nullable();
            $table->string('master_gpa')->nullable();
            $table->string('master_degree')->nullable();
            $table->string('doctoral_university')->nullable();
            $table->string('doctoral_major')->nullable();
            $table->string('doctoral_start_year')->nullable();
            $table->string('doctoral_end_year')->nullable();
            $table->string('doctoral_certificate')->nullable();
            $table->string('doctoral_gpa')->nullable();
            $table->string('doctoral_degree')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_educations');
    }
};

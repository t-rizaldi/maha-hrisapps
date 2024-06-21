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
        Schema::create('employee_siblings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->index();
            $table->string('sibling_name')->nullable();
            $table->string('sibling_gender')->nullable();
            $table->integer('sibling_age')->nullable();
            $table->string('sibling_last_education')->nullable();
            $table->string('sibling_last_job_title')->nullable();
            $table->string('sibling_last_job_company')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_siblings');
    }
};

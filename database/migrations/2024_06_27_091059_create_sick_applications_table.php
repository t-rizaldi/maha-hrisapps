<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    function up(): void
    {
        Schema::create('sick_applications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->index();
            $table->bigInteger('employee_create_id')->nullable()->index();
            $table->date('sick_start_date')->nullable();
            $table->date('sick_end_date')->nullable();
            $table->text('description');
            $table->string('attachment')->nullable();
            $table->integer('total_day')->nullable();
            $table->integer('total_first_month')->nullable();
            $table->integer('total_second_month')->nullable();
            $table->string('sick_branch')->nullable()->index();
            $table->boolean('is_read')->default(false);
            $table->integer('approved_status')->default(0);
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('sick_applications');
    }
};

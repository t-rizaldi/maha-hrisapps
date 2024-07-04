<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->index();
            $table->bigInteger('employee_create_id')->nullable()->index();
            $table->date('leave_start_date');
            $table->date('leave_end_date')->nullable();
            $table->text('description');
            $table->string('attachment')->nullable();
            $table->integer('total_day')->nullable();
            $table->integer('total_first_month')->nullable();
            $table->integer('total_second_month')->nullable();
            $table->string('leave_branch')->nullable()->index();
            $table->boolean('is_read')->default(false);
            $table->integer('approved_status')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};

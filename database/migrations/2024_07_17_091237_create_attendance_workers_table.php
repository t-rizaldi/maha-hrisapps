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
        Schema::create('attendance_workers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('worker_id')->index();
            $table->date('attendance_date');
            $table->time('entry_schedule')->nullable();
            $table->time('home_schedule')->nullable();
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->time('break_start')->nullable();
            $table->time('break_finish')->nullable();
            $table->time('overtime_start')->nullable();
            $table->time('overtime_finish')->nullable();
            $table->string('photo_in')->nullable();
            $table->string('photo_out')->nullable();
            $table->string('location_in')->nullable();
            $table->string('location_out')->nullable();
            $table->string('overtime_start_photo')->nullable();
            $table->string('overtime_finish_photo')->nullable();
            $table->string('overtime_start_location')->nullable();
            $table->string('overtime_finish_location')->nullable();
            $table->string('work_hour_code')->nullable();
            $table->integer('clock_in_type')->default(1)->comment('1 = foto, 2 = finger');
            $table->integer('clock_out_type')->default(1)->comment('1 = foto, 2 = finger');
            $table->boolean('is_late')->default(false);
            $table->boolean('early_out')->default(false)->comment('0 = pulang tetap waktu 1 = pulang cepat');
            $table->integer('clock_in_status')->default(1)->comment('0 = menunggu approve 1 = absen dalam radius 2 = absen luar radius dalam zona 3 = absen luar zona 4 = absen ditolak 5 = umlk ditambah');
            $table->integer('clock_out_status')->default(1)->comment('0 = menunggu approve 1 = absen dalam radius 2 = absen luar radius dalam zona 3 = absen luar zona 4 = absen ditolak 5 = umlk ditambah');
            $table->text('statement_in_rejected')->nullable();
            $table->text('statement_out_rejected')->nullable();
            $table->boolean('clock_in_zone')->default(true)->comment('0 = luar zona 1 = dalam zona');
            $table->boolean('clock_out_zone')->default(true)->comment('0 = luar zona 1 = dalam zona');
            $table->integer('meal_num')->default(0);
            $table->string('branch_attendance')->nullable();
            $table->boolean('create_status')->default(false)->comment('0 = otomatis 1 = manual input');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_workers');
    }
};

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
        Schema::create('work_hours', function (Blueprint $table) {
            $table->id();
            $table->string('work_hour_code');
            $table->string('work_hour_name');
            $table->time('start_entry_hour');
            $table->time('entry_hour');
            $table->time('end_entry_hour');
            $table->time('home_hour');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_hours');
    }
};

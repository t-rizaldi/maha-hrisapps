<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holidays_date');
            $table->string('holidays_name');
            $table->boolean('status')->default(0)->comment('0 = Libur Nasional, 1 = Cuti Bersama');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};

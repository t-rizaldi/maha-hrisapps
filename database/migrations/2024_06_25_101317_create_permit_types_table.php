<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permit_types', function (Blueprint $table) {
            $table->id();
            $table->char('type', 2)->nullable()->comment('i == izin, c == cuti');
            $table->string('name')->nullable();
            $table->integer('total_day')->nullable();
            $table->boolean('is_yearly')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_types');
    }
};

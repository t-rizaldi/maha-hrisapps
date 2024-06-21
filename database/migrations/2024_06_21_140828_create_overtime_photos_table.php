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
        Schema::create('overtime_photos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('overtime_id')->index();
            $table->string('photo');
            $table->tinyInteger('status')->comment('1 = lembur mulai 2 = lembur berlangsung 3 = lembur selesai');
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_photos');
    }
};

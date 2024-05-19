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
        Schema::create('letter_lists', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_receiving_id')->nullable();
            $table->bigInteger('employee_creator_id')->nullable();
            $table->string('category_code')->nullable();
            $table->string('letter_number')->nullable();
            $table->text('subject')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_lists');
    }
};

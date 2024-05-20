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
        Schema::create('employee_biodatas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->index();
            $table->string('fullname')->nullable();
            $table->string('nickname')->nullable();
            $table->string('nik')->nullable();
            $table->bigInteger('identity_province')->nullable();
            $table->bigInteger('identity_regency')->nullable();
            $table->bigInteger('identity_district')->nullable();
            $table->bigInteger('identity_village')->nullable();
            $table->bigInteger('identity_postal_code')->nullable();
            $table->text('identity_address')->nullable();
            $table->bigInteger('current_province')->nullable();
            $table->bigInteger('current_regency')->nullable();
            $table->bigInteger('current_district')->nullable();
            $table->bigInteger('current_village')->nullable();
            $table->bigInteger('current_postal_code')->nullable();
            $table->text('current_address')->nullable();
            $table->string('residence_status')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('emergency_phone_number')->nullable();
            $table->date('start_work')->nullable();
            $table->string('gender', 5)->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->integer('weight')->nullable();
            $table->integer('height')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_biodatas');
    }
};

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
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('nik');
            $table->string('fullname');
            $table->string('phone_number')->nullable();
            $table->text('current_address')->nullable();
            $table->bigInteger('job_title_id')->index();
            $table->string('branch_code')->index();
            $table->string('salary')->nullable();
            $table->string('meal_cost')->nullable()->default(0);
            $table->bigInteger('bank_id')->index();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('photo')->nullable();
            $table->integer('status')->default(1)->comment("0 = nonaktif, 1 = aktif");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};

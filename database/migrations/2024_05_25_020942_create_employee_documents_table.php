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
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->index();
            $table->string('photo')->nullable();
            $table->string('ktp')->nullable();
            $table->string('kk')->nullable();
            $table->string('certificate')->nullable();
            $table->string('grade_transcript')->nullable();
            $table->string('certificate_skill')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('npwp')->nullable();
            $table->string('bpjs_ktn')->nullable();
            $table->string('bpjs_kes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};

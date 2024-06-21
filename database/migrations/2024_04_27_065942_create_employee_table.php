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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->nullable();
            $table->string('fullname');
            $table->string('email')->nullable();
            $table->bigInteger('job_title_id')->nullable()->index();
            $table->string('phone_number')->nullable();
            $table->string('photo')->nullable();
            $table->bigInteger('department_id')->nullable()->index();
            $table->string('branch_code')->nullable()->index();
            $table->string('password')->nullable();
            $table->string('signature')->nullable();
            $table->string('integrity_pact_num')->nullable();
            $table->boolean('integrity_pact_check')->default(false);
            $table->date('integrity_pact_check_date')->nullable();
            $table->boolean('statement_letter_check')->default(false);
            $table->date('statement_letter_check_date')->nullable();
            $table->bigInteger('contract_id')->nullable()->index();
            $table->bigInteger('old_contract_id')->nullable()->index();
            $table->string('employee_status')->nullable();
            $table->string('salary')->nullable();
            $table->boolean('show_contract')->default(true);
            $table->string('employee_letter_code')->nullable();
            $table->boolean('biodata_confirm')->default(false);
            $table->date('biodata_confirm_date')->nullable();
            $table->text('current_address')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->integer('role_id')->default(1);
            $table->integer('status')->default(0)->comment("0 = verifikasi register, 1 = pengisian data, 2 = verifikasi data, 3 = aktif, 4 = nonaktif, 5 = daftar hitam, 6 = view contract register, 7 = verifikasi register ditolak, 8 = verifikasi data ditolak");
            $table->boolean('is_daily')->default(false);
            $table->boolean('is_flexible_absent')->default(false);
            $table->string('device_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

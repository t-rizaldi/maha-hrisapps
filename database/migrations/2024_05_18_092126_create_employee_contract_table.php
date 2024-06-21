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
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->index();
            $table->string('letter_number')->nullable()->index();
            $table->bigInteger('letter_id')->nullable()->index();
            $table->bigInteger('job_title_id')->nullable()->index();
            $table->bigInteger('department_id')->nullable()->index();
            $table->string('branch_code')->nullable()->index();
            $table->string('contract_status')->nullable();
            $table->string('salary')->nullable();
            $table->text('project')->nullable();
            $table->integer('contract_length_num')->nullable();
            $table->string('contract_length_time')->nullable();
            $table->date('start_contract')->nullable();
            $table->date('end_contract')->nullable();
            $table->text('jobdesk_content')->nullable();
            $table->boolean('check_contract')->default(0);
            $table->dateTime('check_contract_datetime')->nullable();
            $table->bigInteger('approver_id')->nullable()->index();
            $table->bigInteger('approver_job_title')->nullable()->index();
            $table->boolean('confirm_contract')->default(0);
            $table->date('confirm_contract_date')->nullable();
            $table->text('contract_file')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};

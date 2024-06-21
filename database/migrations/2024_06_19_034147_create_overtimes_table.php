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
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->index();
            $table->bigInteger('boss_id')->index()->nullable();
            $table->date('overtime_date')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->text('subject')->nullable();
            $table->text('description')->nullable();
            $table->integer('approved_status')->default(11)->comment("0 : Pending Mgr 1: Pending GM 2: Pending HRD 3 : Pending Direktur 4 : Pending Komisaris 5 : Approve 6 : Mgr tolak, 7 : GM tolak 8 : HRD tolak 9 : Direktur tolak 10: komisaris tolak 11 : proses input");
            $table->date('manager_approve_date')->nullable();
            $table->date('gm_approve_date')->nullable();
            $table->date('hrd_approve_date')->nullable();
            $table->date('director_approve_date')->nullable();
            $table->date('commisioner_approve_date')->nullable();
            $table->date('approved_date')->nullable();
            $table->boolean('is_read')->default(0);
            $table->string('overtime_branch')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};

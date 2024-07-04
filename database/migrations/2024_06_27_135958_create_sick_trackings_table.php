<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sick_trackings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sick_id')->index();
            $table->text('description')->nullable();
            $table->text('description_rejected')->nullable();
            $table->integer('status')->nullable()->comment('0 : Diperiksa Mgr 1: Diperiksa GM 2: Diperiksa HRD 3 : Diperiksa Direktur 4 : Diperiksa Komisaris 5 : Approve 6 : Mgr tolak, 7 : GM tolak 8 : HRD tolak 9 : Direktur tolak 10: komisaris tolak 11 : proses input');
            $table->dateTime('datetime')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sick_trackings');
    }
};

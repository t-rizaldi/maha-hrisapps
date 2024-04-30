<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndonesiaRegency extends Model
{
    use HasFactory;
    protected $table = "indonesia_regencies";

    public function province() {
        return $this->belongsTo(IndonesiaProvince::class, 'province_id');
    }
}

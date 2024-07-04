<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SickTracking extends Model
{
    use HasFactory;
    protected $fillable = [
        'sick_id',
        'description',
        'description_rejected',
        'status',
        'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'overtime_id',
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

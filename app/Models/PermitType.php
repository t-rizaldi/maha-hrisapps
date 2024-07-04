<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermitType extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'name',
        'total_day',
        'is_yearly',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}

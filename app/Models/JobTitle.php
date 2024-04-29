<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobTitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department_id',
        'sub_dept',
        'role',
        'is_daily',
        'daily_level',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}

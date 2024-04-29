<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_code',
        'department_name',
        'is_sub',
        'gm_num',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}

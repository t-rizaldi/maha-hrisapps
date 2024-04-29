<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_code',
        'branch_name',
        'branch_location',
        'branch_radius',
        'is_project',
        'is_sub',
        'branch_parent_code',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}

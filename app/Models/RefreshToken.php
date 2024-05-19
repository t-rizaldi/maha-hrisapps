<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'employee_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}

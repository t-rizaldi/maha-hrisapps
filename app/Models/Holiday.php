<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model

{
    use HasFactory;
    protected $fillable = [
        'holidays_date',
        'holidays_name',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}


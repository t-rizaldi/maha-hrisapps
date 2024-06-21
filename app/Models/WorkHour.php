<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_hour_code',
        'work_hour_name',
        'start_entry_hour',
        'entry_hour',
        'end_entry_hour',
        'home_hour',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}

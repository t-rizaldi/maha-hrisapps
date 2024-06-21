<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeChild extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'child_name',
        'child_gender',
        'child_age',
        'child_last_education',
        'child_last_job_title',
        'child_last_job_company',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}

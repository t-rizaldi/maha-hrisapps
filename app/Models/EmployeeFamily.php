<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeFamily extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'father_name',
        'father_status',
        'father_age',
        'father_last_education',
        'father_last_job_title',
        'father_last_job_company',
        'mother_name',
        'mother_status',
        'mother_age',
        'mother_last_education',
        'mother_last_job_title',
        'mother_last_job_company',
        'marital_status',
        'couple_name',
        'couple_age',
        'couple_last_education',
        'couple_last_job_title',
        'couple_last_job_company',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}

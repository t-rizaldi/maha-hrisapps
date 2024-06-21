<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSibling extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'sibling_name',
        'sibling_gender',
        'sibling_age',
        'sibling_last_education',
        'sibling_last_job_title',
        'sibling_last_job_company',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}

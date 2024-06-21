<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'letter_number',
        'letter_id',
        'job_title_id',
        'department_id',
        'branch_code',
        'contract_status',
        'salary',
        'project',
        'contract_lenght_num',
        'contract_lenght_date',
        'start_contract',
        'end_contract',
        'jobdesk_content',
        'check_contract',
        'check_contract_date',
        'approver_id',
        'approver_job_title',
        'confirm_contract',
        'confirm_contract_date',
        'contract_file',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $appends = ['salary_text'];

    public function getSalaryTextAttribute()
    {
        return numericText($this->attributes['salary']);
    }
}

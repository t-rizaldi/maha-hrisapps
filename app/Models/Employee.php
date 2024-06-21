<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'fullname',
        'email',
        'job_title_id',
        'phone_number',
        'photo',
        'department_id',
        'branch_code',
        'password',
        'signature',
        'integrity_pact_num',
        'integrity_pact_check',
        'integrity_pact_check_date',
        'statement_letter_check',
        'statement_letter_check_date',
        'contract_id',
        'old_contract_id',
        'employee_status',
        'salary',
        'show_contract',
        'employee_letter_code',
        'biodata_confirm',
        'biodata_confirm_date',
        'current_address',
        'bank_account_number',
        'role_id',
        'status',
        'is_daily',
        'is_flexible_absent',
        'device_token',
    ];

    protected $hidden = [
        'signature',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['signature_url'];

    public function getSignatureUrlAttribute()
    {
        if(!empty($this->attributes['signature'])) {
            return url('public/storage/' . $this->attributes['signature']);
        } else {
            return null;
        }
    }

    public function jobTitle() {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function department() {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function branch() {
        return $this->belongsTo(Branch::class, 'branch_code', 'branch_code');
    }

    public function contract() {
        return $this->belongsTo(EmployeeContract::class, 'contract_id');
    }

    public function biodata() {
        return $this->hasOne(EmployeeBiodata::class, 'employee_id', 'id');
    }

    public function education() {
        return $this->hasOne(EmployeeEducation::class, 'employee_id', 'id');
    }

    public function family() {
        return $this->hasOne(EmployeeFamily::class, 'employee_id', 'id');
    }

    public function document() {
        return $this->hasOne(EmployeeDocument::class, 'employee_id', 'id');
    }

    public function workHour() {
        return $this->hasOne(EmployeeWorkHour::class, 'employee_id', 'id');
    }
}

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
        'created_at',
        'updated_at',
    ];

    public function contract() {
        return $this->belongsTo(EmployeeContract::class, 'contract_id');
    }

    public function biodata() {
        return $this->hasOne(EmployeeBiodata::class, 'employee_id', 'id');
    }
}

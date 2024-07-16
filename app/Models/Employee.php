<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable implements MustVerifyEmail
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
        'statement_rejected',
        'is_daily',
        'is_flexible_absent',
        'is_overtime',
        'overtime_limit',
        'device_token',
    ];

    protected $hidden = [
        'photo',
        'signature',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'photo_url',
        'signature_url',
        'status_label'
    ];

    public function getSignatureUrlAttribute()
    {
        if(!empty($this->attributes['signature'])) {
            return url('public/storage/' . $this->attributes['signature']);
        } else {
            return null;
        }
    }

    public function getPhotoUrlAttribute()
    {
        if(!empty($this->attributes['photo'])) {
            return url('public/storage/' . $this->attributes['photo']);
        } else {
            return null;
        }
    }

    public function getStatusLabelAttribute()
    {
        $status = $this->getAttribute('status');
        $statusLabel = '';

        switch ($status) {
            case '0':
                $statusLabel = 'Verifikasi Register';
                break;
            case '1':
                $statusLabel = 'Pengisian Data';
                break;
            case '2':
                $statusLabel = 'Verifikasi Data HR Recruitment';
                break;
            case '3':
                $statusLabel = 'Aktif';
                break;
            case '4':
                $statusLabel = 'Nonaktif';
                break;
            case '5':
                $statusLabel = 'Daftar Hitam';
                break;
            case '6':
                $statusLabel = 'Meninjau Kontrak';
                break;
            case '7':
                $statusLabel = 'Registrasi Ditolak';
                break;
            case '8':
                $statusLabel = 'Data Ditolak';
                break;
            case '9':
                $statusLabel = 'Verifikasi Data HR Manager';
                break;
            case '10':
                $statusLabel = 'Verifikasi Kontrak';
                break;
            case '11':
                $statusLabel = 'Data di tolak HR Manager';
                break;
            default:
                $statusLabel = '-';
                break;
        }

        return $statusLabel;
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

    public function bank()
    {
        return $this->hasOne(EmployeeBank::class, 'employee_id', 'id');
    }
}

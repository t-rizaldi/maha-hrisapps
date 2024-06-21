<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'photo',
        'ktp',
        'kk',
        'certificate',
        'bank_account',
        'npwp',
        'bpjs_ktn',
        'bpjs_kes',
    ];

    protected $hidden = [
        'photo',
        'ktp',
        'kk',
        'certificate',
        'bank_account',
        'npwp',
        'bpjs_ktn',
        'bpjs_kes',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'photo_url',
        'ktp_url',
        'kk_url',
        'certificate_url',
        'bank_account_url',
        'npwp_url',
        'bpjs_ktn_url',
        'bpjs_kes_url',
    ];

    public function getPhotoUrlAttribute()
    {
        if(!empty($this->attributes['photo'])) {
            return url('public/storage/' . $this->attributes['photo']);
        } else {
            return null;
        }
    }

    public function getKtpUrlAttribute()
    {
        if(!empty($this->attributes['ktp'])) {
            return url('public/storage/' . $this->attributes['ktp']);
        } else {
            return null;
        }
    }

    public function getKkUrlAttribute()
    {
        if(!empty($this->attributes['kk'])) {
            return url('public/storage/' . $this->attributes['kk']);
        } else {
            return null;
        }
    }

    public function getCertificateUrlAttribute()
    {
        if(!empty($this->attributes['certificate'])) {
            return url('public/storage/' . $this->attributes['certificate']);
        } else {
            return null;
        }
    }

    public function getBankAccountUrlAttribute()
    {
        if(!empty($this->attributes['bank_account'])) {
            return url('public/storage/' . $this->attributes['bank_account']);
        } else {
            return null;
        }
    }

    public function getNpwpUrlAttribute()
    {
        if(!empty($this->attributes['npwp'])) {
            return url('public/storage/' . $this->attributes['npwp']);
        } else {
            return null;
        }
    }

    public function getBpjsKtnUrlAttribute()
    {
        if(!empty($this->attributes['bpjs_ktn'])) {
            return url('public/storage/' . $this->attributes['bpjs_ktn']);
        } else {
            return null;
        }
    }

    public function getBpjsKesUrlAttribute()
    {
        if(!empty($this->attributes['bpjs_kes'])) {
            return url('public/storage/' . $this->attributes['bpjs_kes']);
        } else {
            return null;
        }
    }
}

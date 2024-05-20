<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeBiodata extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'fullname',
        'nickname',
        'nik',
        'identity_province',
        'identity_regency',
        'identity_district',
        'identity_village',
        'identity_postal_code',
        'identity_address',
        'current_province',
        'current_regency',
        'current_district',
        'current_village',
        'current_postal_code',
        'current_address',
        'residence_status',
        'phone_number',
        'emergency_phone_number',
        'start_work',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'blood_type',
        'weight',
        'height',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}

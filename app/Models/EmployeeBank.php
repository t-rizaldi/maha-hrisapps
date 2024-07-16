<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeBank extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'bank_id',
        'account_number',
        'account_name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id', 'id');
    }
}

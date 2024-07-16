<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'fullname',
        'phone_number',
        'current_address',
        'job_title_id',
        'branch_code',
        'salary',
        'meal_cost',
        'bank_id',
        'bank_account_number',
        'bank_account_name',
        'photo',
        'status',
    ];

    protected $hidden = [
        'photo',
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'photo_url'
    ];

    public function getPhotoUrlAttribute()
    {
        if(!empty($this->attributes['photo'])) {
            return url('public/storage/' . $this->attributes['photo']);
        } else {
            return null;
        }
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_code', 'branch_code');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function document()
    {
        return $this->hasMany(WorkerDocument::class, 'worker_id', 'id');
    }
}

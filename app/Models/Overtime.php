<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'boss_id',
        'overtime_date',
        'start_time',
        'end_time',
        'subject',
        'description',
        'approved_status',
        'manager_approve_date',
        'gm_approve_date',
        'hrd_approve_date',
        'director_approve_date',
        'commisioner_approve_date',
        'approved_date',
        'is_read',
        'overtime_branch',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $appends = ['approved_status_label'];

    public function getApprovedStatusLabelAttribute()
    {
        return structureApprovalStatusLabel($this->getAttribute('approved_status'));
    }
}

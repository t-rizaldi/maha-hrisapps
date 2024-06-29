<?php

namespace App\Models;

use App\Http\Controllers\BaseController;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    private $baseController;

    public function __construct()
    {
        $this->baseController = new BaseController();
    }

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

    protected $appends = [
        'approved_status_label',
        'employee'
    ];

    public function getApprovedStatusLabelAttribute()
    {
        return structureApprovalStatusLabel($this->getAttribute('approved_status'));
    }

    public function getEmployeeAttribute()
    {
        $employeeId = $this->getAttribute('employee_id');
        $employee = $this->baseController->getEmployee($employeeId);

        if($employee['status'] == 'success'){
            return $employee['data'];
        } else {
            return null;
        }
    }

    public function tracking()
    {
        return $this->hasMany(OvertimeTracking::class, 'overtime_id', 'id');
    }

    public function photo()
    {
        return $this->hasMany(OvertimePhoto::class, 'overtime_id', 'id');
    }
}

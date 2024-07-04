<?php

namespace App\Models;

use App\Http\Controllers\BaseController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    private $baseController;

    public function __construct()
    {
        $this->baseController = new BaseController();
    }

    use HasFactory;
    protected $fillable = [
        'employee_id',
        'employee_create_id',
        'leave_start_date',
        'leave_end_date',
        'description',
        'attachment',
        'total_day',
        'total_first_month',
        'total_second_month',
        'leave_branch',
        'is_read',
        'approved_status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
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
        if ($employee['status'] == 'success') {
            return $employee['data'];
        } else {
            return null;
        }
    }

    public function tracking()
    {
        return $this->hasMany(LeaveTracking::class, 'leave_id');
    }
}

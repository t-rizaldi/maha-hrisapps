<?php

namespace App\Models;

use App\Http\Controllers\BaseController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    private $baseController;

    public function __construct()
    {
        $this->baseController = new BaseController();
    }

    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'entry_schedule',
        'home_schedule',
        'clock_in',
        'clock_out',
        'break_start',
        'break_finish',
        'overtime_start',
        'overtime_finish',
        'photo_in',
        'photo_out',
        'location_in',
        'location_out',
        'overtime_start_photo',
        'overtime_finish_photo',
        'overtime_start_location',
        'overtime_finish_location',
        'work_hour_code',
        'clock_in_type',
        'clock_out_type',
        'is_late',
        'early_out',
        'clock_in_status',
        'clock_out_status',
        'statement_in_rejected',
        'statement_out_rejected',
        'clock_in_zone',
        'clock_out_zone',
        'meal_num',
        'branch_attendance',
        'create_status',
    ];

    protected $hidden = [
        'photo_in',
        'photo_out',
        'overtime_start_photo',
        'overtime_finish_photo',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'employee',
        'photo_in_url',
        'photo_out_url',
        'overtime_start_photo_url',
        'overtime_finish_photo_url',
    ];

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

    public function getPhotoInUrlAttribute()
    {
        if(!empty($this->attributes['photo_in'])) {
            return url('public/storage/' . $this->attributes['photo_in']);
        } else {
            return null;
        }
    }

    public function getPhotoOutUrlAttribute()
    {
        if(!empty($this->attributes['photo_out'])) {
            return url('public/storage/' . $this->attributes['photo_out']);
        } else {
            return null;
        }
    }

    public function getOvertimeStartPhotoUrlAttribute()
    {
        if(!empty($this->attributes['overtime_start_photo'])) {
            return url('public/storage/' . $this->attributes['overtime_start_photo']);
        } else {
            return null;
        }
    }

    public function getOvertimeFinishPhotoUrlAttribute()
    {
        if(!empty($this->attributes['overtime_finish_photo'])) {
            return url('public/storage/' . $this->attributes['overtime_finish_photo']);
        } else {
            return null;
        }
    }
 }

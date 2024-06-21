<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeWorkHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function sundayCode()
    {
        return $this->belongsTo(WorkHour::class, 'sunday', 'work_hour_code');
    }

    public function mondayCode()
    {
        return $this->belongsTo(WorkHour::class, 'monday', 'work_hour_code');
    }

    public function tuesdayCode()
    {
        return $this->belongsTo(WorkHour::class, 'tuesday', 'work_hour_code');
    }

    public function wednesdayCode()
    {
        return $this->belongsTo(WorkHour::class, 'wednesday', 'work_hour_code');
    }

    public function thursdayCode()
    {
        return $this->belongsTo(WorkHour::class, 'thursday', 'work_hour_code');
    }

    public function fridayCode()
    {
        return $this->belongsTo(WorkHour::class, 'friday', 'work_hour_code');
    }

    public function saturdayCode()
    {
        return $this->belongsTo(WorkHour::class, 'saturday', 'work_hour_code');
    }
}

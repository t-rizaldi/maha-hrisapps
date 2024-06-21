<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeEducation extends Model
{
    use HasFactory;

    protected $table = 'employee_educations';

    protected $fillable = [
        'employee_id',
        'last_education',
        'primary_school',
        'ps_start_year',
        'ps_end_year',
        'ps_certificate',
        'ps_gpa',
        'junior_high_school',
        'jhs_start_year',
        'jhs_end_year',
        'jhs_certificate',
        'jhs_gpa',
        'senior_high_school',
        'shs_start_year',
        'shs_end_year',
        'shs_certificate',
        'shs_gpa',
        'bachelor_university',
        'bachelor_major',
        'bachelor_start_year',
        'bachelor_end_year',
        'bachelor_certificate',
        'bachelor_gpa',
        'bachelor_degree',
        'master_university',
        'master_major',
        'master_start_year',
        'master_end_year',
        'master_certificate',
        'master_gpa',
        'master_degree',
        'doctoral_university',
        'doctoral_major',
        'doctoral_start_year',
        'doctoral_end_year',
        'doctoral_certificate',
        'doctoral_gpa',
        'doctoral_degree',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'last_education_major'
    ];

    public function getLastEducationMajorAttribute()
    {
        $val = $this->attributes['last_education'];
        $diplomaVal = ['d i', 'd ii', 'd iii'];

        if(in_array($val, $diplomaVal)) {
            return $this->attributes['bachelor_major'];
        } else if($val == 's1') {
            return $this->attributes['bachelor_major'];
        } else if ($val == 's2') {
            return $this->attributes['master_major'];
        } else if($val == 's3') {
            return $this->attributes['doctoral_major'];
        } else {
            return null;
        }
    }
}

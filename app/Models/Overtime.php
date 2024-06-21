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
        $status = $this->getAttribute('approved_status');
        $statusLabel = 'Pending -';

        switch ($status) {
            case '0':
                $statusLabel = 'Pending Manager';
                break;
            case '1':
                $statusLabel = 'Pending GM';
                break;
            case '2':
                $statusLabel = 'Pending HRD';
                break;
            case '3':
                $statusLabel = 'Pending Direktur';
                break;
            case '4':
                $statusLabel = 'Pending Komisaris';
                break;
            case '5':
                $statusLabel = 'Approved';
                break;
            case '6':
                $statusLabel = 'Ditolak Manager';
                break;
            case '7':
                $statusLabel = 'Ditolak GM';
                break;
            case '8':
                $statusLabel = 'Ditolak HRD';
                break;
            case '9':
                $statusLabel = 'Ditolak Direktur';
                break;
            case '10':
                $statusLabel = 'Ditolak Komisaris';
                break;
            case '11':
                $statusLabel = 'Proses Input';
                break;
            default:
                $statusLabel = 'Pending -';
                break;
        }

        return $statusLabel;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimePhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'overtime_id',
        'photo',
        'status',
        'location'
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
        $photo = $this->getAttribute('photo');

        if(!empty($photo)) {
            return url('public/storage/' . $photo);
        } else {
            return null;
        }
    }
}

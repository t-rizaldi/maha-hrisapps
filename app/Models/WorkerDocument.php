<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'document_type',
        'description',
        'document',
    ];

    protected $hidden = [
        'document',
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'document_url'
    ];

    public function getDocumentUrlAttribute()
    {
        if(!empty($this->attributes['document'])) {
            return url('public/storage/' . $this->attributes['document']);
        } else {
            return null;
        }
    }
}

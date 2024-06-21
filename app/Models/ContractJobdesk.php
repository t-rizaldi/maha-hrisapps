<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractJobdesk extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'jobdesk'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}

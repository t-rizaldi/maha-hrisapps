<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterList extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_receiving_id',
        'employee_creator_id',
        'category_code',
        'letter_number',
        'subject',
        'description',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}

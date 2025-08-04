<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Graduate extends Model
{
    protected $table = 'graduates';

    protected $fillable = [
        'name',
        'phone',
        'age',
        'specialized',
        'company',
        'profile',
        'photo',
        'experience'
    ];
}

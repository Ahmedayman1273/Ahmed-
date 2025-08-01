<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'start_time',
        'image',
    ];

    protected $casts = [
        'start_time' => 'datetime',
    ];
}

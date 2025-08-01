<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $table = 'requests'; // ضروري علشان الاسم مش جمع تقليدي

    protected $fillable = [
        'name',
        'description',
        'price',
    ];

    public $timestamps = true; // موجود في الجدول
}

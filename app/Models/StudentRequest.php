<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentRequest extends Model
{
    use HasFactory;

    protected $table = 'student_requests';

protected $fillable = [
    'user_id',
    'request_id',
    'count',
    'total_price',
    'receipt_image',
    'student_id',       
    'student_name_ar',
    'student_name_en',
    'department',
    'status',
];


    public $timestamps = true;

    // علاقة باليوزر
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // علاقة بنوع الطلب (إثبات قيد، شهادة تخرج...)
    public function requestType()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}

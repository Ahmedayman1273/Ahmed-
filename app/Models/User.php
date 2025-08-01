<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'type',
        'major',
        'phone_number',
        'personal_email',
        'profile_photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // العلاقات
    public function posts()
    {
        return $this->hasMany(Post::class, 'posted_by');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'evented_by');
    }

    public function certificateRequests()
    {
        return $this->hasMany(CertificateRequest::class);
    }

    public function enrollmentProofRequests()
    {
        return $this->hasMany(EnrollmentProofRequest::class);
    }
    public function studentRequests()
{
    return $this->hasMany(\App\Models\StudentRequest::class);
}
}

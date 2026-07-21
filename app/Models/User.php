<?php

namespace App\Models;

use App\Traits\HasAccessCode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, HasAccessCode;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'surname',
        'gender',
        'custom_gender',
        'semester',
        'email',
        'phone',
        'birthdate',
        'program_id',
        'avatar',
        'access_code',
        'access_code_expires_at',
        'canvas_user_id'
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'access_code', 'access_code_expires_at', 'canvas_user_id'];

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    public function points()
    {
        return $this->hasMany(UserPoint::class, 'user_id');
    }

    public function eventParticipations()
    {
        return $this->hasMany(EventParticipant::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'surname',
        'gender',
        'custom_gender',
        'semester',
        'email',
        'password',
        'phone',
        'birthdate',
        'program_id',
        'avatar'
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];

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

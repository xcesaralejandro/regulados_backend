<?php

namespace App\Models;

use App\Models\CustomPivots\EventUserMapping;
use App\Traits\HasAccessCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'canvas_user_id',
        'instagram',
        'discord'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $with = ['program.university'];

    protected $hidden = [
        'gender',
        'custom_gender',
        'program_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'access_code',
        'access_code_expires_at',
        'canvas_user_id'
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_user_mapping')
            ->using(EventUserMapping::class)
            ->withPivot(['workflow_state', 'role'])
            ->withTimestamps()
            ->whereNull('event_user_mapping.deleted_at');
    }

    public function sentContactRequests(): HasMany
    {
        return $this->hasMany(ContactRequest::class, 'sender_id');
    }

    public function receivedContactRequests(): HasMany
    {
        return $this->hasMany(ContactRequest::class, 'receiver_id');
    }
}

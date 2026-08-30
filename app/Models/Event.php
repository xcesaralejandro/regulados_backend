<?php

namespace App\Models;

use App\Models\CustomPivots\EventUserMapping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'events';
    protected $fillable = [
        'user_id',
        'event_category_id',
        'repeat_code',
        'title',
        'description',
        'location',
        'notes',
        'visibility',
        'start_at',
        'end_at'
    ];
    protected $hidden = ['user_id', 'event_category_id', 'deleted_at'];
    protected $with = ['participants', 'actions'];

    protected $casts = ['created_at' => 'datetime:Y-m-d H:i:s', 'updated_at' => 'datetime:Y-m-d H:i:s'];

    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user_mapping')
            ->using(EventUserMapping::class)
            ->withPivot(['workflow_state', 'role'])
            ->withTimestamps()
            ->whereNull('event_user_mapping.deleted_at');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(EventAction::class, 'event_id');
    }
}

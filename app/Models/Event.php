<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    protected $hidden = ['deleted_at'];

    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

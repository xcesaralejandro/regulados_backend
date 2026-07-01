<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventAttendee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'event_attendees';
    protected $fillable = ['event_id', 'user_id', 'state'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

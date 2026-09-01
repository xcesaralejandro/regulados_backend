<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventAction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'event_actions';

    protected $fillable = [
        'event_id',
        'user_id',
        'title',
        'description',
        'order',
        'completed_by',
        'completed_at',
        'source'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'completed_at' => 'datetime:Y-m-d H:i:s'
    ];

    protected $hidden = ['deleted_at', 'completed_by', 'updated_at', 'event_id', 'user_id'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by', 'id');
    }
}

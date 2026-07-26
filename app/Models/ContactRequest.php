<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactRequest extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'contact_requests';

  protected $fillable = [
    'sender_id',
    'receiver_id',
    'workflow_state',
    'deleted_by',
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
  ];

  protected $with = ['sender', 'receiver'];

  public function scopeBetweenUsers(Builder $query, int $userA, int $userB): Builder
  {
    return $query->where(function ($q) use ($userA, $userB) {
      $q->where('sender_id', $userA)
        ->where('receiver_id', $userB);
    })->orWhere(function ($q) use ($userA, $userB) {
      $q->where('sender_id', $userB)
        ->where('receiver_id', $userA);
    });
  }

  public function scopeFromTo(Builder $query, int $fromUserId, int $toUserId): Builder
  {
    return $query->where('sender_id', $fromUserId)
      ->where('receiver_id', $toUserId);
  }

  public function sender(): BelongsTo
  {
    return $this->belongsTo(User::class, 'sender_id');
  }

  public function receiver(): BelongsTo
  {
    return $this->belongsTo(User::class, 'receiver_id');
  }

  public function deleter(): BelongsTo
  {
    return $this->belongsTo(User::class, 'deleted_by');
  }
}

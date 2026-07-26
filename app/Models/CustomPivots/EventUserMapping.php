<?php


namespace App\Models\CustomPivots;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventUserMapping extends Pivot
{
  use SoftDeletes;

  protected $table = 'event_user_mapping';

  protected $fillable = [
    'event_id',
    'user_id',
    'workflow_state',
    'role',
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
  ];

  protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

  public function isConfirmed(): bool
  {
    return $this->workflow_state === 'confirmed';
  }

  public function isAdmin(): bool
  {
    return $this->role === 'admin';
  }
}

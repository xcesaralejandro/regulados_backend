<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Score extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'scores';
    protected $fillable = ['user_id', 'points', 'context', 'action', 'reference_id', 'reference_table'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

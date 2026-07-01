<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'programs';
    protected $fillable = ['university_id', 'name'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function university()
    {
        return $this->belongsTo(University::class, 'university_id', 'id');
    }
}

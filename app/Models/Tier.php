<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tier extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tiers';
    protected $fillable = ['name', 'description', 'min_points'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
}

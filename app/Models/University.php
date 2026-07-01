<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class University extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'universities';
    protected $fillable = ['name', 'short_name', 'canvas_domain_url', 'canvas_client_id', 'canvas_client_secret'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function programs()
    {
        return $this->hasMany(Program::class, 'university_id');
    }
}

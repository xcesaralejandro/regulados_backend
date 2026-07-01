<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'event_categories';
    protected $fillable = ['name', 'description'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function events()
    {
        return $this->hasMany(Event::class, 'event_category_id');
    }
}

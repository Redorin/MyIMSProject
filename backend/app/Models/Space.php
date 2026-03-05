<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Space extends Model
{
    // default values applied when creating new instances without explicit values
    protected $attributes = [
        'occupancy' => 0,
        'status' => 'low',
    ];

    protected $fillable = ['name', 'capacity', 'occupancy', 'status', 'type', 'code'];
}

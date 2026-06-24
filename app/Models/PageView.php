<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    protected $fillable = [
        'view_date',
        'path',
        'route_name',
        'count',
        'last_viewed_at',
    ];

    protected $casts = [
        'view_date' => 'date',
        'last_viewed_at' => 'datetime',
    ];
}

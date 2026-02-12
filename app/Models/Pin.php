<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Pin extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'latitude',
        'longitude',
        'store_name',
        'status',
        'comment',
        'tags',
        'image_path',
        'user_id',
        'shop_id',
    ];

    protected $casts = [
        'type' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'status' => 'integer',
        'tags' => 'array',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }
        return \Storage::disk('public')->url($this->image_path);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}

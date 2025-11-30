<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
        'latitude',
        'longitude',
        'status',
        'secret_key',
    ];

    /**
     * ステータスの定数
     */
    const STATUS_AVAILABLE = 0; // 空き
    const STATUS_WAITING = 1;   // 待ち
    const STATUS_FULL = 2;       // 満席

    /**
     * ステータスのラベルを取得
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_AVAILABLE => '空き',
            self::STATUS_WAITING => '待ち',
            self::STATUS_FULL => '満席',
            default => '不明',
        };
    }

    /**
     * ステータスのバッジカラーを取得
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_AVAILABLE => 'green',
            self::STATUS_WAITING => 'yellow',
            self::STATUS_FULL => 'red',
            default => 'gray',
        };
    }
}

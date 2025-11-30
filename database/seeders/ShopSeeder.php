<?php

namespace Database\Seeders;

use App\Models\Shop;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shops = [
            [
                'name' => '理容タナカ',
                'phone' => '078-123-4567',
                'address' => '兵庫県神戸市中央区三宮町1-1-1',
                'latitude' => 34.6901,
                'longitude' => 135.1956,
                'status' => Shop::STATUS_AVAILABLE,
                'secret_key' => Str::random(32),
            ],
            [
                'name' => 'カットサロン・エース',
                'phone' => '078-234-5678',
                'address' => '兵庫県神戸市中央区元町通2-2-2',
                'latitude' => 34.6883,
                'longitude' => 135.1933,
                'status' => Shop::STATUS_WAITING,
                'secret_key' => Str::random(32),
            ],
            [
                'name' => 'バーバーひかり',
                'phone' => '078-345-6789',
                'address' => '兵庫県神戸市灘区六甲道町3-3-3',
                'latitude' => 34.7234,
                'longitude' => 135.2389,
                'status' => Shop::STATUS_AVAILABLE,
                'secret_key' => Str::random(32),
            ],
        ];

        foreach ($shops as $shop) {
            Shop::create($shop);
        }
    }
}

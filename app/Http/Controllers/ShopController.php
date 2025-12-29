<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * ユーザー用一覧画面
     */
    public function index()
    {
        $shops = Shop::orderBy('name')->get();
        
        return view('shops.index', compact('shops'));
    }

    /**
     * 店舗用操作画面
     */
    public function edit($secret_key)
    {
        $shop = Shop::where('secret_key', $secret_key)->firstOrFail();
        
        return view('shops.edit', compact('shop'));
    }

    /**
     * ステータス更新処理
     */
    public function update(Request $request, $secret_key)
    {
        $shop = Shop::where('secret_key', $secret_key)->firstOrFail();
        
        $request->validate([
            'status' => 'required|integer|in:0,1,2',
        ]);
        
        $shop->status = $request->status;
        $shop->save();
        
        return redirect()
            ->route('shops.edit', ['secret_key' => $secret_key])
            ->with('success', 'ステータスを更新しました');
    }

    /**
     * 位置情報に基づく近くの店舗検索API
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0|max:50', // デフォルト10km
        ]);

        $userLat = $request->latitude;
        $userLng = $request->longitude;
        $radius = $request->radius ?? 10; // デフォルト10km

        // Haversine公式を使用して距離計算
        $shops = Shop::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function ($shop) use ($userLat, $userLng) {
                $distance = $this->calculateDistance(
                    $userLat,
                    $userLng,
                    $shop->latitude,
                    $shop->longitude
                );
                $shop->distance = round($distance, 2);
                // status_labelを明示的に追加
                $shop->status_label = $shop->status_label;
                return $shop;
            })
            ->filter(function ($shop) use ($radius) {
                return $shop->distance <= $radius;
            })
            ->sortBy('distance')
            ->values();

        return response()->json($shops);
    }

    /**
     * Haversine公式で2点間の距離を計算（km単位）
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // 地球の半径（km）

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}

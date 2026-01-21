<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
        $radius = $request->radius ?? 1; // デフォルト10km

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

        // category, updated_at, secret_keyも含めて返す
        return response()->json($shops->map(function ($shop) {
            $shop->category = $shop->category;
            $shop->updated_at = $shop->updated_at ? $shop->updated_at->toDateTimeString() : null;
            $shop->secret_key = $shop->secret_key;
            return $shop;
        }));
    }

    /**
     * 店舗ステータス更新API（secret_key経由）
     */
    public function updateStatus(Request $request, $secret_key)
    {
        try {
            $shop = Shop::where('secret_key', $secret_key)->firstOrFail();
            
            $request->validate([
                'status' => 'required|integer|in:0,1,2',
            ]);
            
            $shop->status = $request->status;
            $shop->save();
            
            if ($request->expectsJson()) {
                $updatedShop = $shop->fresh();
                return response()->json([
                    'success' => true,
                    'message' => 'ステータスを更新しました',
                    'shop' => [
                        'id' => $updatedShop->id,
                        'name' => $updatedShop->name,
                        'category' => $updatedShop->category,
                        'status' => $updatedShop->status,
                        'status_label' => $updatedShop->status_label,
                        'updated_at' => $updatedShop->updated_at ? $updatedShop->updated_at->toDateTimeString() : null,
                        'secret_key' => $updatedShop->secret_key,
                    ],
                ]);
            }
            
            return redirect()
                ->route('shops.edit', ['secret_key' => $secret_key])
                ->with('success', 'ステータスを更新しました');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '入力内容に誤りがあります',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '店舗が見つかりません',
                ], 404);
            }
            abort(404);
        } catch (\Exception $e) {
            \Log::error('Shop status update error: ' . $e->getMessage(), [
                'exception' => $e,
                'secret_key' => $secret_key,
                'request' => $request->all(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ステータスの更新に失敗しました: ' . $e->getMessage(),
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'ステータスの更新に失敗しました');
        }
    }

    /**
     * 店舗名更新API（secret_key経由）
     */
    public function updateName(Request $request, $secret_key)
    {
        try {
            $shop = Shop::where('secret_key', $secret_key)->firstOrFail();
            
            $request->validate([
                'name' => 'nullable|string|max:255',
            ]);
            
            $shop->name = $request->name;
            $shop->save();
            
            if ($request->expectsJson()) {
                $updatedShop = $shop->fresh();
                return response()->json([
                    'success' => true,
                    'message' => '店舗名を更新しました',
                    'shop' => [
                        'id' => $updatedShop->id,
                        'name' => $updatedShop->name,
                        'category' => $updatedShop->category,
                        'status' => $updatedShop->status,
                        'status_label' => $updatedShop->status_label,
                        'updated_at' => $updatedShop->updated_at ? $updatedShop->updated_at->toDateTimeString() : null,
                        'secret_key' => $updatedShop->secret_key,
                    ],
                ]);
            }
            
            return redirect()
                ->route('shops.edit', ['secret_key' => $secret_key])
                ->with('success', '店舗名を更新しました');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '入力内容に誤りがあります',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '店舗が見つかりません',
                ], 404);
            }
            abort(404);
        } catch (\Exception $e) {
            \Log::error('Shop name update error: ' . $e->getMessage(), [
                'exception' => $e,
                'secret_key' => $secret_key,
                'request' => $request->all(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '店舗名の更新に失敗しました: ' . $e->getMessage(),
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', '店舗名の更新に失敗しました');
        }
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

    /**
     * 匿名店舗登録フォーム表示（モーダル用）
     */
    public function createGuest()
    {
        // モーダルはフロントエンドで表示するため、特にデータは不要
        // 必要に応じて業種のリストなどを返すことも可能
        return response()->json([
            'categories' => [
                'ヘアーサロン',
                '飲食',
                'ボディケア',
            ],
        ]);
    }

    /**
     * 匿名店舗登録処理
     */
    public function storeGuest(Request $request)
    {
        try {
            $validated = $request->validate([
                'category' => 'required|string|in:ヘアーサロン,飲食,ボディケア',
                'name' => 'nullable|string|max:255',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ]);

            // secret_keyを自動生成
            $validated['secret_key'] = Str::random(32);
            $validated['status'] = Shop::STATUS_AVAILABLE;
            $validated['user_id'] = null; // 匿名登録
            $validated['phone'] = ''; // 空文字列
            $validated['address'] = ''; // 空文字列

            $shop = Shop::create($validated);

            // JSONレスポンスを返す（Ajax対応）
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '店舗を登録しました',
                    'shop' => $shop,
                ]);
            }

            // 通常のフォーム送信の場合
            return redirect()
                ->route('shops.index')
                ->with('success', '店舗を登録しました');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // バリデーションエラーの場合
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '入力内容に誤りがあります',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            // データベースエラーなどの例外をキャッチ
            \Log::error('Shop registration error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            if ($request->expectsJson()) {
                $message = '店舗の登録に失敗しました';
                // データベースエラーの場合、より具体的なメッセージを返す
                if (str_contains($e->getMessage(), 'category')) {
                    $message = 'データベースにカラムが存在しません。マイグレーションを実行してください: php artisan migrate';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', '店舗の登録に失敗しました');
        }
    }
}

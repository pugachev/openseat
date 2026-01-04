<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminShopController extends Controller
{
    /**
     * 店舗一覧（管理者用）
     */
    public function index()
    {
        $shops = Shop::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('admin.shops.index', compact('shops'));
    }

    /**
     * 新規登録フォーム
     */
    public function create()
    {
        return view('admin.shops.create');
    }

    /**
     * 店舗登録処理
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'nullable|integer|in:0,1,2',
        ]);

        // 緯度経度未入力ならGeocoding.jpで自動取得
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            try {
                $client = new Client(['timeout' => 5]);
                $res = $client->get('https://www.geocoding.jp/api/', [
                    'query' => [
                        'q' => trim($validated['address']),
                    ],
                    'headers' => [
                        'User-Agent' => 'OpenSeat/1.0 (your@email.com)'
                    ]
                ]);
                $xml = simplexml_load_string($res->getBody()->getContents());
                \Log::info('Geocoding.jp API response:', (array)$xml);
                if (isset($xml->coordinate->lat) && isset($xml->coordinate->lng)) {
                    $validated['latitude'] = (string)$xml->coordinate->lat;
                    $validated['longitude'] = (string)$xml->coordinate->lng;
                }
            } catch (\Exception $e) {
                Log::error('Geocoding.jp geocode failed: ' . $e->getMessage());
            }
        }

        // secret_keyを自動生成
        $validated['secret_key'] = Str::random(32);
        $validated['status'] = $validated['status'] ?? Shop::STATUS_AVAILABLE;
        $validated['user_id'] = auth()->id();
        $shop = Shop::create($validated);

        return redirect()
            ->route('admin.shops.show', $shop->id)
            ->with('success', '店舗を登録しました');
    }

    /**
     * 店舗詳細（URL・QRコード表示）
     */
    public function show($id)
    {
        $shop = Shop::findOrFail($id);
        
        // ステータス更新URLを生成
        $controlUrl = route('shops.edit', ['secret_key' => $shop->secret_key]);
        
        // QRコードを生成（SVG形式）
        $qrCode = QrCode::size(300)
            ->generate($controlUrl);

        return view('admin.shops.show', compact('shop', 'controlUrl', 'qrCode'));
    }

    /**
     * 店舗編集フォーム
     */
    public function edit($id)
    {
        $shop = Shop::findOrFail($id);
        
        return view('admin.shops.edit', compact('shop'));
    }

    /**
     * 店舗更新処理
     */
    public function update(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'nullable|integer|in:0,1,2',
        ]);

        $shop->update($validated);

        return redirect()
            ->route('admin.shops.index')
            ->with('success', '店舗情報を更新しました');
    }

    /**
     * 店舗削除処理
     */
    public function destroy($id)
    {
        $shop = Shop::findOrFail($id);
        $shop->delete();

        return redirect()
            ->route('admin.shops.index')
            ->with('success', '店舗を削除しました');
    }
}


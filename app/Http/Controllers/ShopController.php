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
}

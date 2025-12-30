<?php

use App\Http\Controllers\AdminShopController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

// ユーザー向けルート
Route::get('/', [ShopController::class, 'index'])->name('shops.index');
Route::get('/api/shops/nearby', [ShopController::class, 'nearby'])->name('shops.nearby');
Route::get('/shop/control/{secret_key}', [ShopController::class, 'edit'])->name('shops.edit');
Route::post('/shop/control/{secret_key}/update', [ShopController::class, 'update'])->name('shops.update');

// 管理者向けルート
Route::prefix('admin')->name('admin.')->group(function () {
    // /admin にアクセスした場合は /admin/shops にリダイレクト
    Route::get('/', function () {
        return redirect()->route('admin.shops.index');
    });

    Route::resource('shops', AdminShopController::class);
});

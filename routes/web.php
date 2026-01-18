<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\RegisterController;
use App\Http\Controllers\AdminShopController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

// ユーザー向けルート
Route::get('/', [ShopController::class, 'index'])->name('shops.index');
Route::get('/api/shops/nearby', [ShopController::class, 'nearby'])->name('shops.nearby');
Route::get('/shop/control/{secret_key}', [ShopController::class, 'edit'])->name('shops.edit');
Route::post('/shop/control/{secret_key}/update', [ShopController::class, 'update'])->name('shops.update');
Route::post('/api/shops/{secret_key}/status', [ShopController::class, 'updateStatus'])->name('shops.status.update');

// 匿名店舗登録ルート（認証不要）
Route::get('/shop/register', [ShopController::class, 'createGuest'])->name('shops.register');
Route::post('/shop/register', [ShopController::class, 'storeGuest'])->name('shops.register.store');

// 管理者向け認証ルート（認証不要）
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// 管理者向けルート（認証必須）
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    // /admin にアクセスした場合は /admin/shops にリダイレクト
    Route::get('/', function () {
        return redirect()->route('admin.shops.index');
    });

    Route::resource('shops', AdminShopController::class);
});

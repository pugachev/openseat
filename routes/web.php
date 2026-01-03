<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\RegisterController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;

// ユーザー向けルート
Route::get('/', [ShopController::class, 'index'])->name('shops.index');
Route::get('/api/shops/nearby', [ShopController::class, 'nearby'])->name('shops.nearby');
Route::get('/shop/control/{secret_key}', [ShopController::class, 'edit'])->name('shops.edit');
Route::post('/shop/control/{secret_key}/update', [ShopController::class, 'update'])->name('shops.update');


// 管理者向け認証ルート（認証不要）
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // 管理者用 店舗一覧・CRUD
    Route::get('/shops', [\App\Http\Controllers\AdminShopController::class, 'index'])->name('shops.index');
    Route::get('/shops/create', [\App\Http\Controllers\AdminShopController::class, 'create'])->name('shops.create');
    Route::post('/shops', [\App\Http\Controllers\AdminShopController::class, 'store'])->name('shops.store');
    Route::get('/shops/{shop}/edit', [\App\Http\Controllers\AdminShopController::class, 'edit'])->name('shops.edit');
    Route::put('/shops/{shop}', [\App\Http\Controllers\AdminShopController::class, 'update'])->name('shops.update');
    Route::delete('/shops/{shop}', [\App\Http\Controllers\AdminShopController::class, 'destroy'])->name('shops.destroy');
    Route::get('/shops/{shop}', [\App\Http\Controllers\AdminShopController::class, 'show'])->name('shops.show');
});



Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

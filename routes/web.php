<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\RegisterController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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
});

// 管理者向けルート（認証必須）
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    // /admin にアクセスした場合は /admin/shops にリダイレクト
    Route::get('/', function () {
        return redirect()->route('admin.shops.index');
    });

    Route::resource('shops', AdminShopController::class);
Route::get('/', function () {
    return view('welcome');
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

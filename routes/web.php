<?php

use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'index'])->name('shops.index');
Route::get('/api/shops/nearby', [ShopController::class, 'nearby'])->name('shops.nearby');
Route::get('/shop/control/{secret_key}', [ShopController::class, 'edit'])->name('shops.edit');
Route::post('/shop/control/{secret_key}/update', [ShopController::class, 'update'])->name('shops.update');

<?php

use App\Http\Controllers\Guest\BrandController;
use App\Http\Controllers\Guest\CartController;
use App\Http\Controllers\Guest\CategoryController;
use App\Http\Controllers\Guest\HomeController;
use App\Http\Controllers\Guest\PaymentController;
use App\Http\Controllers\Guest\ProductController;
use Illuminate\Support\Facades\Route;

// Public catalogue
Route::get('/home', [HomeController::class, 'index']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brands/{slug}', [BrandController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);

// Guest cart (identified by the X-Guest-Token header)
Route::middleware('guest.cart')->prefix('guest/cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/', [CartController::class, 'store']);
    Route::patch('/{product}', [CartController::class, 'update']);
    Route::delete('/{product}', [CartController::class, 'destroy']);
});

// SSLCommerz callbacks (public by design, validated server side)
Route::match(['get', 'post'], '/payment/success', [PaymentController::class, 'success']);
Route::match(['get', 'post'], '/payment/fail', [PaymentController::class, 'fail']);
Route::match(['get', 'post'], '/payment/cancel', [PaymentController::class, 'cancel']);
Route::post('/payment/ipn', [PaymentController::class, 'ipn']);

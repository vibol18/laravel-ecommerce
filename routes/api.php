<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register/admin-super', [AuthController::class, 'registerAdminSuper']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/payment-methods', [PaymentController::class, 'methods']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);
Route::get('/products/{product}/reviews/{review}', [ReviewController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'updateProfile']);

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'add']);
        Route::put('/items/{cartItem}', [CartController::class, 'update']);
        Route::delete('/items/{cartItem}', [CartController::class, 'remove']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::post('/checkout', [OrderController::class, 'checkout']);
    });

    Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);
    Route::put('/products/{product}/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/products/{product}/reviews/{review}', [ReviewController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);

    Route::get('/orders/admin/all', [OrderController::class, 'adminIndex']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

    Route::get('/admin/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/admin/low-stock', [DashboardController::class, 'lowStock']);

    Route::patch('/payments/{order}/mark-paid', [PaymentController::class, 'markPaid']);
});

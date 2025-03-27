<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Auth\AuthController;




// Public Routes (No Authentication)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);


// Protected Routes (Requires Authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Product Management (Employees Only)
    Route::middleware('role:employee')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);


        // Employee Order Monitoring
        Route::get('/orders/all', [OrderController::class, 'allOrders']); // View all orders
        Route::get('/orders/{id}/details', [OrderController::class, 'orderDetails']); // View specific order details
    });


    // Customer Shopping & Checkout (Customers Only)
    Route::middleware('role:customer')->group(function () {
        // Cart Management      
        Route::post('/cart/add', [CartController::class, 'addToCart']); // Add item to cart
        Route::get('/cart', [CartController::class, 'viewCart']); // View cart
        Route::delete('/cart/clear', [CartController::class, 'clearCart']); // Clear entire cart
        Route::delete('/cart/{id}', [CartController::class, 'removeFromCart']); // Remove item from cart
        Route::put('/cart/{id}', [CartController::class, 'updateCartItem']); // Update cart item quantity


        // Orders & Checkout
        Route::post('/orders', [OrderController::class, 'store']); // Checkout
        Route::get('/orders', [OrderController::class, 'index']); // View customer's orders
        Route::get('/orders/{id}', [OrderController::class, 'show']); // View order details
        Route::post('/checkout', [OrderController::class, 'checkout']);
    });


    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
});





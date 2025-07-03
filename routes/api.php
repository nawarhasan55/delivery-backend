<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

/*Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');*/

Route::middleware('auth:api')->get('/profile', function (Request $request) {
    return $request->user();
});

Route::get('/email/verify/{id}', function ($id, Request $request) {
    if (!URL::hasValidSignature($request)) {
        return response()->json(['message' => 'Invalid or expired link.'], 401);
    }

    $user = \App\Models\User::findOrFail($id);

    if ($user->email_verified_at) {
        return response()->json(['message' => 'Email already verified.']);
    }

    $user->email_verified_at = now();
    $user->save();

    return response()->json(['message' => 'Email verified successfully.']);
})->name('auth.verify');

// Routes for authentication
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::post('logout', [UserController::class, 'logout']);

Route::middleware(['auth:api'])->group(function () {
    Route::post('store_order', [OrderController::class, 'store']);// Route for adding order
    Route::get('/orders/my_orders', [OrderController::class, 'getMyOrders']);// Route for view all order of user
    Route::get('/orders/my_pending_orders', [OrderController::class, 'getMyPendingOrders']);//Route for view pending order of user
    Route::delete('/orders/{id}', [OrderController::class, 'deletePendingOrder']);
    Route::put('/orders/{id}', [OrderController::class, 'updatePendingOrder']);
});
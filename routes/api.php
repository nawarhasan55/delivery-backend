<?php

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

Route::post('register',[UserController::class,'register']);
Route::post('login' ,[UserController::class,'login']);
Route::post('logout' ,[UserController::class,'logout']);

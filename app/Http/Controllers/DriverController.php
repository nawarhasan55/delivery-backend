<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;

class DriverController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            // تحقق إذا كان البريد موجود
            $driver = Driver::where('email', $credentials['email'])->first();

            if (!$driver) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Email not found'
                ], 401);
            }
            // تحقق من كلمة المرور
            if (!auth()->attempt($credentials)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Incorrect password'
                ], 401);
            }
        }
        // نحصل على المستخدم بعد نجاح تسجيل الدخول
        $driver = auth()->user();

        // التحقق من البريد
        /*if (!$driver->email_verified_at) {
            return response()->json([
                'status' => 0,
                'message' => 'Please verify your email first.'
            ], 403);
        }*/

        return response()->json([
            'status' => 1,
            //'message' => 'Login successful',
            //'driver' => auth()->user(),
            'token' => $token,
        ]);
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'status' => 1,
            'message' => 'User successfully signed out'
        ]);
    }
}

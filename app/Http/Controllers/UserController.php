<?php

namespace App\Http\Controllers;

use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
            'role' => [
                'required',
                Rule::in(['admin', 'normal'])
            ]
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()
            ], 400);
        }
        $data = $validator->validated();
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        // إرسال رابط التحقق المؤقت
        $verificationUrl = URL::temporarySignedRoute(
            'auth.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id]
        );
        Mail::to($user->email)->send(new VerifyEmail($verificationUrl));

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 1,
            //'message' => 'added user successfully',
            //'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            // تحقق إذا كان البريد موجود
            $user = User::where('email', $credentials['email'])->first();

            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Email not found'
                ], 401);
            }
            // تحقق من كلمة المرور
            if (!auth()->JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Incorrect password'
                ], 401);
            }
        }
        // نحصل على المستخدم بعد نجاح تسجيل الدخول
        $user = auth()->JWTAuth::user();

        // التحقق من البريد
        if (!$user->email_verified_at) {
            return response()->json([
                'status' => 0,
                'message' => 'Please verify your email first.'
            ], 403);
        }

        return response()->json([
            'status' => 1,
            //'message' => 'Login successful',
            //'user' => auth()->user(),
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

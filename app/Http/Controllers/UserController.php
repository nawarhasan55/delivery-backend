<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
            'role' => [
                'required',
                Rule::in(['admin', 'normal'])
            ]
        ]);
        if($validator->fails()){
            return response()->json([
                'status' => 0,
                'message'=> $validator->errors()], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status'=>1,
            'message' => 'added user successfully',
            'User' => $user,
            'token' => $token
        ],201);
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
                    'error' => 'Email not found'
                ], 401);
            }
            // تحقق من كلمة المرور
            if (!auth()->attempt($credentials)) {
                return response()->json([
                    'status' => 0,
                    'error' => 'Incorrect password'
                ], 401);
            }
        }
        return response()->json([
            'status'=>1,
            'message' => 'Login successful',
            'token' => $token,
            'user' => auth()->user(),
        ]);
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'status' => 1,
            'message' => 'User successfully signed out']);
    }
}
//return response()->json('true');



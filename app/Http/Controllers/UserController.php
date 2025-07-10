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
            $errors = $validator->errors()->all(); // تجيب كل الرسائل كنصوص
            return response()->json([
                'status' => 0,
                'message' => $errors[0]
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

       /* // إرسال رابط التحقق المؤقت
        $verificationUrl = URL::temporarySignedRoute(
            'auth.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id]
        );
        Mail::to($user->email)->send(new VerifyEmail($verificationUrl));*/

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
            if (!auth()->attempt($credentials)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Incorrect password'
                ], 401);
            }
        }
        // نحصل على المستخدم بعد نجاح تسجيل الدخول
        $user = auth()->user();

        // التحقق من البريد
        /*if (!$user->email_verified_at) {
            return response()->json([
                'status' => 0,
                'message' => 'Please verify your email first.'
            ], 403);
        }*/

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

    public function getprofile()
    {
        $user= auth('api')->user();
        return response()->json([
           'status'=> 1,
           'message'=> 'User Profile Fetched',
            //'user'=>$user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user=auth('api')->user();
        $validator= Validator::make($request->all(),[
            'name'=> 'sometimes|required|string|max:255',
            'phone'=>['sometimes','required','string',
                Rule::unique('users')->ignore($user->id),//للتاكد من ان القيمة المدخلة فريدة بين كل المستخدمين ما عدا المستخدم الحالي
                ],
            'email'=>['sometimes','required','email',
                Rule::unique('users')->ignore($user->id),
                ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json([
                'status' =>0,
                'message' =>$errors[0]
            ], 400);
        }
        $data=$validator->validated();
        //نحدث فقط القيم اللي بيوصل
        if(isset($data['name'])){
            $user->name=$data['name'];
        }
        if(isset($data['phone'])){
            $user->phone=$data['phone'];
        }
        if(isset($data['email'])){
            $user->email=$data['email'];
        }
        $user->save();
        return response()->json([
            'status'=>1,
            'message'=>'Profile updated successfully',
            //'user'=>$user
        ]);

    }
}

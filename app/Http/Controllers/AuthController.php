<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;



class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'=> 'required|email',
            'password'=> 'required',
            'account_type' => 'required|in:user,driver'
        ]);

        $credentials = $request->only('email', 'password');
        $accountType = $request->account_type;

        if ($accountType === 'user') {
            $user =User::where('email', $credentials['email'])->first();

            if(!$user) {
                return response()->json([
                    'status' => 0,
                    'message'=> 'Invalid email'
                ], 401);
            }

            if (!Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'status'=> 0,
                    'message' => 'Invalid password'
                ], 401);
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status'=> 1,
                'token' => $token,
                //'account_type' => $user->role,
                //'message' => 'User login successful'
            ]);
        }

        if ($accountType === 'driver') {
            $driver =Driver::where('email', $credentials['email'])->first();

            if (!$driver) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Invalid email'
                ], 401);
            }

            if (!Hash::check($credentials['password'], $driver->password)) {
                return response()->json([
                    'status' => 0,
                    'message'=> 'Invalid password'
                ], 401);
            }

            $token = JWTAuth::fromUser($driver);

            return response()->json([
                'status' => 1,
                'token' => $token,
                //'account_type' => 'driver',
                //'message' => 'Driver login successful'
            ]);
        }

        return response()->json([
            'status' => 0,
            'message' => 'Invalid account type'
        ], 400);
    }
    public function logout()
    {
        auth()->JWTAuth::logout();

        return response()->json([
            'status' => 1,
            'message'=> 'Logged out successfully'
        ]);
    }

    public function getProfile()
    {
        $user =null;

        // نحاول جلب المستخدم من Guard 'api'
        if ($user=auth('api')->user()) {
            return response()->json([
                'status'=> 1,
                'message'=> 'User profile fetched successfully',
                'user'=> $user
            ]);
        }

        // نحاول جلب السائق من Guard 'driver'
        if($user= auth('driver')->user()) {
            return response()->json([
                'status'=> 1,
                'message'=> 'Driver profile fetched successfully',
                'user' => $user
            ]);
        }

        return response()->json([
            'status'=> 0,
            'message'=> 'Unauthorized or Invalid token'
        ], 401);
    }
    public function updateProfile(Request $request)
    {
        $user = null;

        if($user = auth('api')->user()) {
            $guard = 'users';
        } elseif($user = auth('driver')->user()) {
            $guard = 'drivers';
        } else{
            return response()->json([
                'status'=> 0,
                'message'=> 'Unauthorized or Invalid token'
            ], 401);
        }

        $validator= Validator::make($request->all(),[
            'name'=> 'sometimes|required|string|max:255',
            'phone'=> [
                'sometimes', 'required', 'string',
                Rule::unique($guard)->ignore($user->id),
            ],
            'email'=> [
                'sometimes', 'required', 'email',
                Rule::unique($guard)->ignore($user->id),
            ],
            'current_password'=>'required_with:password|string',
            'password'=>'sometimes|required|confirmed|min:8|confirmed',
            'image'=>'sometimes|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if($validator->fails()) {
            return response()->json([
                'status'=> 0,
                'message'=> $validator->errors()->first()
            ], 400);
        }

        $data= $validator->validated();

        //تحقق من كلمة المرور الحالية
        if(isset($data['password'])){
            if(!Hash::check($data['current_password'],$user->password)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            $user->password= Hash::make($data['password']);
        }

        if(isset($data['name'])) $user->name =$data['name'];
        if(isset($data['phone'])) $user->phone =$data['phone'];
        if(isset($data['email'])) $user->email =$data['email'];

        // رفع الصورة الشخصية وحفظها في storage/app/public/profiles
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إن وُجدت
            if ($user->image) {
                Storage::disk('public')->delete('profiles/' . $user->image);
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('profiles', $imageName, 'public');
            $user->image = $imageName;
        }

        $user->save();

        return response()->json([
            'status' => 1,
            'message' => 'Profile updated successfully',
            'data' =>[
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => isset($user->role) ? $user->role : 'driver',
                'image_url' => $user->image ? asset('storage/profiles/' . $user->image) : null
            ]
        ]);
    }
}

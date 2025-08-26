<?php

namespace App\Http\Controllers;

use App\Mail\VerifyEmail;
use App\Models\Notification;
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


    public function getUserNotifications()
    {
        $user = auth('api')->user(); // مستخدم عادي
        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized'
            ], 401);
        }

        $notifications = Notification::where('user_id', $user->id)->where('target','user')
            ->latest()
            ->with(['user', 'driver'])
            ->get()
            ->map(function ($notif) {
                return [
                    'id'         => $notif->id,
                    'title'      => $notif->title,
                    'body'       => $notif->body,
                    'order_id'   => $notif->order_id,
                    'created_at' => $notif->created_at,
                    'user_name'  => optional($notif->user)->name,
                    'driver_name'=> optional($notif->driver)->name,
                ];
            });

        return response()->json([
            'status' => 1,
            'notifications' => $notifications
        ]);
    }


}

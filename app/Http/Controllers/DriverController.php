<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;

class DriverController extends Controller
{
    public function getDriverNotifications()
    {
        $driver = auth('driver')->user(); // عامل التوصيل
        if (!$driver) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized'
            ], 401);
        }

        $notifications = Notification::where('driver_id', $driver->id)->where('target','driver')
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

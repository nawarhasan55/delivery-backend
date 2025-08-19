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

        $notifications = Notification::where('driver_id', $driver->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 1,
            'notifications' => $notifications
        ]);
    }

}

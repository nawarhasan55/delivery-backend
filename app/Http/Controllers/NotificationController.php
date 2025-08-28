<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    public function markAsShown($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'status' => 0,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->update(['show' => true]);
        $notification->save();

        return response()->json([
            'status' => 1,
            'message' => 'Notification marked as shown',
            'notification' => $notification
        ]);
    }

    public function userShown($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'status' => 0,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->update(['user_show' => true]);
        $notification->save();

        return response()->json([
            'status' => 1,
            'message' => 'User show marked as shown',
            'notification' => $notification
        ]);
    }

    public function driverShown($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'status' => 0,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->update(['driver_show' => true]);
        $notification->save();

        return response()->json([
            'status' => 1,
            'message' => 'Driver show marked as shown',
            'notification' => $notification
        ]);
    }
}

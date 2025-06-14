<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {
        $order = Order::create([
            'order_name' => $request->order_name,
            'source' => $request->source,
            'destination' => $request->destination,
            'user_id' => auth('api')->id(),    //user that ordered
            'driver_id' => null,        //not selected yet
        ]);
        return response()->json([
            'status'=>1,
            'message'=>'order added successfully',
            'order'=>$order
        ], 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
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
            //'order'=>$order
        ], 200);
    }
    public function getMyOrders(Request $request)
    {
        $user=auth('api')->user();
        $orders=Order::where('user_id',$user->id)->get();
        return response()->json([
            'status'=>1,
            'message'=> 'Fetched user orders successfully',
            'orders'=>$orders,
        ]);
    }

    public function getMyPendingOrders(Request $request)
    {
        $user=auth('api')->user();
        $pendingOrders=Order::where('user_id',$user->id)->where('status','pending')->get();
        return response()->json([
            'status'=>1,
            'message'=> 'Fetched user pending orders successfully',
            'orders'=>$pendingOrders,
        ]);
    }

    public function deletePendingOrder($id)
    {
        $user=auth('api')->user();
        $order=Order::where('id',$id)->where('user_id',$user->id)
            ->where('status','pending')->first();
        if (!$order) {
            return response()->json([
                'status'=> 0,
                'message'=> 'There are no pending orders'
            ], 404);
        }
        $order->delete();
        return response()->json([
            'status'=> 1,
            'message'=> 'Order deleted successfully'
        ]);
    }
    public function updatePendingOrder(Request $request,$id)
    {
        $user=auth('api')->user();
        $order=Order::where('id',$id)->where('user_id',$user->id)
            ->where('status','pending')->first();
        if (!$order) {
            return response()->json([
                'status'=> 0,
                'message'=> 'There are no pending orders'
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'order_name' => 'sometimes|required|string',
            'source' => 'sometimes|required|string',
            'destination' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json([
                'status' => 0,
                'message' => $errors[0]
            ], 400);
        }
        $order->update($validator->validated());

        return response()->json([
            'status'=> 1,
            'message'=> 'Order update successfully'
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
class OrderController extends Controller
{
    //قيام المستخدم بطلب طلبية
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
    //قيام المستخدم بعرض طلباته
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

    //قيام المستخدم بعرض طلباته pending
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
    //قيام المستخدم بحذف طلبه
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
    //قيام المستخدم بتعديل طلبه
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

    //قيام عامل التوصيل بقبول طلب
    public function acceptOrder(Request $request, $orderId)
    {
        $driver= auth('driver')->user();
        if(!$driver){
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized'
            ],401);
        }
        $order = Order::where('id', $orderId)
                      ->where('status', 'pending')
                      ->first();
        if (!$order) {
            return response()->json([
                'status'=> 0,
                'message'=> 'Order not found or already taken'
            ],404);
        }

        $order->status='in_progress';
        $order->driver_id=$driver->id;
        $order->save();

        return response()->json([
            'status'=> 1,
            'message'=> 'Order accepted successfully',
            'order'=> $order
        ]);
    }

    //قيام عامل التوصيل بعرض الطلب الذي اختاره
    public function currentOrder()
    {
        $driver=auth('driver')->user();

        if(!$driver) {
            return response()->json([
                'status'=> 0,
                'message'=>'Unauthorized'
            ],401);
        }

        $order = Order::where('driver_id', $driver->id)
            ->where('status', 'in_progress')
            ->with(['user:id,name,phone']) // نجلب اسم المستخدم ورقمه
            ->first();

        if(!$order) {
            return response()->json([
                'status'=>0,
                'message'=>'No active order found'
            ]);
        }

        return response()->json([
            'status'=>1,
            'message'=>'Current order fetched successfully',
            'data'=> $order
        ]);
    }

    //قيام عامل التوصيل بعرض كل طلبات pending
    public function listPendingOrders()
    {
        $driver= auth('driver')->user();

        if (!$driver) {
            return response()->json([
                'status'=>0,
                'message'=>'Unauthorized'
            ], 401);
        }

        $orders=Order::where('status', 'pending')
            ->whereNull('driver_id')
            ->with('user:id,name,phone') // معلومات المستخدم الذي أنشأ الطلب
            ->get();

        return response()->json([
            'status'=>1,
            'message'=>'Pending orders fetched successfully',
            'data'=>$orders
        ]);
    }

    //قيام عامل التوصيل بجعل حالة الطلب completed بعد توصيله الطلب
    public function completeOrder($orderId)
    {
        $driver = auth('driver')->user();

        if (!$driver) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized'
            ], 401);
        }

        $order = Order::where('id', $orderId)
            ->where('driver_id', $driver->id)
            ->where('status', 'in_progress')
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 0,
                'message' => 'No active order found'
            ]);
        }

        $order->status = 'completed';
        $order->save();

        return response()->json([
            'status' => 1,
            'message' => 'Order marked as completed successfully',
            'data' => $order
        ]);
    }

    //قيام عامل التوصيل بعرض كل الطلبات لديه حالته completed
    public function listCompletedOrders()
    {
        $driver = auth('driver')->user();

        $orders = $driver->orders()
            ->where('status', 'completed')
            ->with('user:id,name,phone') // لجلب اسم ورقم المستخدم المرتبط بالطلب
            ->get();

        return response()->json([
            'status' => 1,
            'message' => 'Completed orders fetched successfully',
            'data' => $orders,
        ]);
    }

}

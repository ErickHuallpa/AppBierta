<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function availableOrders()
    {
        $orders = Order::where('status', 'pending_delivery_assignment')
                    ->with(['user.persona', 'location', 'items.product'])
                    ->get();
        return response()->json($orders);
    }

    public function acceptOrder($id, Request $request)
    {
        $user = $request->user() ?? \App\Models\User::where('role', 'delivery')->first();
        
        $order = Order::findOrFail($id);
        
        if ($order->user_id === $user->id) {
            return response()->json(['message' => 'Por políticas de seguridad, no puedes auto-asignarte un pedido que tú mismo realizaste.'], 403);
        }

        if ($order->status !== 'pending_delivery_assignment') {
            return response()->json(['message' => 'El pedido ya fue asignado o cancelado'], 400);
        }

        $order->update([
            'delivery_user_id' => $user->id,
            'status' => 'in_transit'
        ]);

        return response()->json($order);
    }

    public function show($id, Request $request)
    {
        $user = $request->user() ?? \App\Models\User::where('role', 'delivery')->first();
        
        $order = Order::where('id', $id)
                    ->where('delivery_user_id', $user->id)
                    ->with(['user.persona', 'location', 'items.product'])
                    ->firstOrFail();

        return response()->json($order);
    }

    public function myDeliveries(Request $request)
    {
        $user = $request->user() ?? \App\Models\User::where('role', 'delivery')->first();
        
        $orders = Order::where('delivery_user_id', $user->id)
                    ->whereIn('status', ['in_transit', 'delivered'])
                    ->with(['user.persona', 'location', 'items.product'])
                    ->get();
        return response()->json($orders);
    }

    public function markAsDelivered(Request $request, $id)
    {
        $user = $request->user() ?? \App\Models\User::where('role', 'delivery')->first();
        
        $order = Order::where('id', $id)->where('delivery_user_id', $user->id)->firstOrFail();
        
        $order->status = 'delivered';
        $order->save();

        return response()->json(['message' => 'Pedido marcado como entregado.']);
    }
}

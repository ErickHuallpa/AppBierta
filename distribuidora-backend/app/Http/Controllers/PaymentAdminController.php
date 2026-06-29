<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class PaymentAdminController extends Controller
{
    public function pendingPayments()
    {
        $orders = Order::where('payment_status', 'pending')
                    ->with(['user', 'location'])
                    ->get();
        return response()->json($orders);
    }

    public function approve(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->user_id === $request->user()->id) {
            return response()->json(['error' => 'Por políticas de seguridad, no puedes aprobar tu propio pago. Otro miembro del personal debe hacerlo.'], 403);
        }

        $order->payment_status = 'confirmed';
        $order->status = $order->order_type === 'pickup' ? 'ready_for_pickup' : 'pending_delivery_assignment';
        $order->save();

        return response()->json(['message' => 'Pago confirmado. Pedido listo para ser asignado a un delivery.']);
    }

    public function reject(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->user_id === $request->user()->id) {
            return response()->json(['error' => 'Por políticas de seguridad, no puedes rechazar tu propio pago. Otro miembro del personal debe hacerlo.'], 403);
        }

        $order->payment_status = 'rejected';
        $order->status = 'cancelled';
        $order->save();

        return response()->json(['message' => 'Pago rechazado. Pedido cancelado.']);
    }
}

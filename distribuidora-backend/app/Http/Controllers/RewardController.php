<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function redeem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = clone $request->user();
        if(!$user) {
            $user = \App\Models\User::first(); // Mock
        }

        $product = \App\Models\Product::find($request->product_id);

        if ($product->points_cost == 0) {
            return response()->json(['error' => 'Este producto no se puede canjear por puntos.'], 422);
        }

        if ($product->stock < $request->quantity) {
            return response()->json(['error' => 'No hay suficiente stock.'], 422);
        }

        $totalPointsNeeded = $product->points_cost * $request->quantity;

        if ($user->loyalty_points < $totalPointsNeeded) {
            return response()->json(['error' => 'No tienes suficientes puntos. Necesitas: ' . $totalPointsNeeded], 422);
        }

        // Crear el pedido a costo 0
        $order = \App\Models\Order::create([
            'user_id' => $user->id,
            'total_amount' => 0,
            'delivery_cost' => 0,
            'status' => 'completed',
            'is_point_exchange' => true
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'price_at_time' => 0 // Canje
        ]);

        // Descontar Puntos y Stock
        $user->loyalty_points -= $totalPointsNeeded;
        $user->save();

        $product->stock -= $request->quantity;
        $product->save();

        return response()->json([
            'message' => 'Canje realizado exitosamente.',
            'points_remaining' => $user->loyalty_points
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function attemptOrder(Request $request)
    {
        // Esta ruta se llama cuando el usuario en la app de Ionic empieza el proceso de pago
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $user = $request->user(); // Asumiendo autenticación (Sanctum/Passport) en el futuro
        if(!$user) return response()->json(['error' => 'No auth'], 401);

        $quota = \App\Models\UserProductQuota::firstOrCreate(
            ['user_id' => $user->id, 'product_id' => $request->product_id]
        );

        if ($quota->attempted && $quota->quantity_bought == 0) {
            $diffMinutes = now()->diffInMinutes($quota->updated_at);
            if ($diffMinutes < 30) {
                return response()->json(['error' => 'Ya utilizaste tu único intento para este producto. Debes esperar ' . (30 - $diffMinutes) . ' minutos para volver a intentarlo.'], 403);
            }
        }

        // Marcar como intentado
        $quota->attempted = true;
        $quota->save();

        return response()->json(['message' => 'Intento registrado, puedes proceder al pago.']);
    }

    public function store(Request $request)
    {
        $rules = [
            'items' => 'required|array',
            'order_type' => 'required|in:delivery,pickup',
            'location_id' => 'required_if:order_type,delivery',
            'payment_method' => 'required|in:qr,cash,points'
        ];

        if (!$request->boolean('is_pos')) {
            $rules['payment_receipt'] = 'required_if:payment_method,qr|image|mimes:jpeg,png,jpg|max:2048';
        }

        $request->validate($rules);

        // 1. Validar Hora (Solo hasta las 4 PM)
        $now = now();
        $enforceTime = \Illuminate\Support\Facades\Cache::get('enforce_time_limit', true);
        if ($enforceTime && $now->hour >= 16) {
            return response()->json(['error' => 'Los pedidos solo se realizan hasta las 16:00 (4:00 PM).'], 403);
        }

        $user = $request->user();
        if ($request->boolean('is_pos') && $request->has('client_id')) {
            $user = \App\Models\User::findOrFail($request->client_id);
        } else if (!$user) {
            $user = \App\Models\User::first();
        }

        $items = $request->input('items', []);
        $totalAmount = 0;
        $totalPointsCost = 0;
        
        // 2. Validar Stock y Cupos
        foreach ($items as $item) {
            $product = \App\Models\Product::find($item['product_id']);
            if (!$product || $product->real_stock < $item['quantity']) {
                return response()->json(['error' => 'No hay suficiente stock para el producto: ' . ($product->name ?? '')], 422);
            }

            $isReward = isset($item['is_reward']) && $item['is_reward'] == true;

            if ($isReward) {
                if (!$product->points_cost || $product->points_cost <= 0) {
                    return response()->json(['error' => 'El producto ' . $product->name . ' no está disponible para canje con puntos.'], 422);
                }
                $totalPointsCost += ($product->points_cost * $item['quantity']);
            } else {
                $price = $product->promotional_price ?? $product->precio_venta;
                $totalAmount += ($price * $item['quantity']);
            }

            $quota = \App\Models\UserProductQuota::firstOrCreate(
                ['user_id' => $user->id, 'product_id' => $product->id]
            );

            if ($product->max_quota_per_user) {
                if (($quota->quantity_bought + $item['quantity']) > $product->max_quota_per_user) {
                    return response()->json(['error' => 'Excedes el cupo permitido para este lote de: ' . $product->name], 422);
                }
            }
        }

        if ($totalPointsCost > 0) {
            if ($user->loyalty_points < $totalPointsCost) {
                return response()->json(['error' => 'No tienes suficientes puntos para realizar este canje. Necesitas ' . $totalPointsCost . ' puntos.'], 422);
            }
        }

        // 3. Lógica de Costo de Envío
        $deliveryCost = 0;
        $suggestedIncrease = 0;
        $todayDayOfWeek = $now->dayOfWeekIso; // 1 (Lunes) a 7 (Domingo)
        
        // Si hay monto a pagar en efectivo, verificamos costo de envío
        if ($totalAmount > 0 && $request->order_type === 'delivery') {
            if ($user->delivery_route_day !== $todayDayOfWeek) {
                // No es su ruta, verificamos si supera los 1500 Bs
                if ($totalAmount < 1500) {
                    $deliveryCost = 15; // Costo base (ejemplo 15 Bs)
                    $suggestedIncrease = 1500 - $totalAmount;
                }
            }
        }

        if ($suggestedIncrease > 0 && !$request->boolean('accept_delivery_cost')) {
            return response()->json([
                'requires_confirmation' => true,
                'message' => 'El pedido no llega a 1500 Bs y hoy no es su día de ruta. Aumente su pedido en ' . $suggestedIncrease . ' Bs para envío gratis, o acepte el cargo extra de envío.',
                'delivery_cost' => $deliveryCost
            ], 200); // Se envía 200 con flag de confirmación
        }

        $receiptPath = null;
        if ($request->hasFile('payment_receipt')) {
            $receiptPath = $request->file('payment_receipt')->store('receipts', 'public');
        }

        // 4. Crear el pedido
        $finalStatus = 'pending_payment';
        if ($request->payment_method === 'cash' || $request->payment_method === 'points') {
            $finalStatus = $request->order_type === 'pickup' ? 'ready_for_pickup' : 'pending_delivery_assignment';
        }

        $paymentStatus = ($request->payment_method === 'cash' || $request->payment_method === 'points') ? 'confirmed' : 'pending';

        if ($request->boolean('is_pos')) {
            $finalStatus = 'delivered'; // Se entrega físicamente en el acto
            $paymentStatus = 'confirmed'; // El cajero verificó el pago
        }

        $isPointExchangeOnly = ($totalAmount == 0 && $totalPointsCost > 0);

        $order = \App\Models\Order::create([
            'user_id' => $user->id,
            'total_amount' => $totalAmount,
            'delivery_cost' => $request->order_type === 'delivery' ? $deliveryCost : 0,
            'status' => $finalStatus,
            'is_point_exchange' => $isPointExchangeOnly,
            'payment_method' => $request->payment_method,
            'payment_receipt' => $receiptPath,
            'payment_status' => $paymentStatus,
            'location_id' => $request->order_type === 'delivery' ? $request->location_id : null,
            'order_type' => $request->order_type,
            'notes' => $request->notes,
            'nit' => $request->nit,
            'razon_social' => $request->razon_social
        ]);

        $pointsEarned = 0;

        foreach ($items as $item) {
            $product = \App\Models\Product::find($item['product_id']);
            $targetProductId = $product->parent_id ? $product->parent_id : $product->id;
            $quantityToFulfill = $item['quantity'] * ($product->parent_id ? $product->package_multiplier : 1);
            
            $isReward = isset($item['is_reward']) && $item['is_reward'] == true;

            $orderItem = \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id, // Maintain original product in order
                'quantity' => $item['quantity'],
                'price_at_time' => $isReward ? 0 : ($product->promotional_price ?? $product->precio_venta)
            ]);

            // Descontar stock por lotes (FIFO) del producto objetivo
            $batches = \App\Models\ProductBatch::where('product_id', $targetProductId)
                ->whereIn('status', ['active', 'promotion'])
                ->where('quantity_current', '>', 0)
                ->orderBy('created_at', 'asc') // Lotes más antiguos primero
                ->get();

            foreach ($batches as $batch) {
                if ($quantityToFulfill <= 0) break;

                $take = min($batch->quantity_current, $quantityToFulfill);
                $batch->quantity_current -= $take;
                if ($batch->quantity_current == 0) {
                    $batch->status = 'exhausted';
                }
                $batch->save();

                \App\Models\OrderItemBatch::create([
                    'order_item_id' => $orderItem->id,
                    'product_batch_id' => $batch->id,
                    'quantity' => $take
                ]);

                $quantityToFulfill -= $take;
            }

            // Actualizar stock total del producto objetivo
            $targetProduct = \App\Models\Product::find($targetProductId);
            $targetProduct->stock -= ($item['quantity'] * ($product->parent_id ? $product->package_multiplier : 1));
            $targetProduct->save();

            // Aumentar cupo comprado
            $quota = \App\Models\UserProductQuota::where('user_id', $user->id)->where('product_id', $product->id)->first();
            $quota->quantity_bought += $item['quantity'];
            $quota->save();

            if (!$isReward) {
                $pointsEarned += ($product->points_reward * $item['quantity']);
            }
        }

        // 5. Asignar/Descontar puntos al usuario
        if ($totalPointsCost > 0) {
            $user->loyalty_points -= $totalPointsCost;
        }
        if ($pointsEarned > 0) {
            $user->loyalty_points += $pointsEarned;
        }
        $user->save();

        return response()->json([
            'message' => 'Pedido realizado exitosamente',
            'order_id' => $order->id,
            'points_earned' => $pointsEarned,
            'total' => $totalAmount + $deliveryCost,
            'receipt' => 'Generado'
        ]);
    }

    public function myOrders(Request $request)
    {
        $user = $request->user();
        if(!$user) {
            $user = \App\Models\User::first();
        }

        $orders = \App\Models\Order::where('user_id', $user->id)
            ->with(['items.product', 'deliveryUser', 'location'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    public function myPurchasedProducts(Request $request)
    {
        $user = $request->user();
        if(!$user) return response()->json([]);

        $productIds = \App\Models\UserProductQuota::where('user_id', $user->id)
            ->where('quantity_bought', '>', 0)
            ->pluck('product_id');

        return response()->json($productIds);
    }

    public function rateDelivery(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5'
        ]);

        $order = \App\Models\Order::findOrFail($id);
        
        if ($order->status !== 'delivered' && $order->status !== 'entregado') {
            return response()->json(['error' => 'Solo puedes calificar pedidos entregados.'], 403);
        }

        $order->rating = $request->rating;
        $order->save();

        return response()->json(['message' => 'Gracias por tu calificación.']);
    }
}

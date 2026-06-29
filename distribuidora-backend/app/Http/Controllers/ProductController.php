<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::all());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $user->role !== 'employee') {
            return response()->json(['error' => 'No tienes permisos para registrar productos.'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'precio_venta' => 'required|numeric',
            'stock' => 'nullable|integer',
            'points_reward' => 'required|integer',
            'points_cost' => 'required|integer',
            'max_quota_per_user' => 'nullable|integer',
            'image_url' => 'nullable|string',
            'precio_compra' => 'nullable|numeric',
            'expiry_date' => 'nullable|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'parent_id' => 'nullable|exists:products,id',
            'package_multiplier' => 'nullable|integer|min:1'
        ]);

        $product = Product::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'precio_venta' => $request->precio_venta,
            'stock' => $request->stock ?? 0,
            'points_reward' => $request->points_reward,
            'points_cost' => $request->points_cost,
            'max_quota_per_user' => $request->max_quota_per_user,
            'image_url' => $request->image_url,
            'parent_id' => $request->parent_id,
            'package_multiplier' => $request->package_multiplier ?? 1,
        ]);

        if ($request->stock > 0) {
            \App\Models\ProductBatch::create([
                'product_id' => $product->id,
                'supplier_id' => $request->supplier_id,
                'quantity_initial' => $request->stock,
                'quantity_current' => $request->stock,
                'purchase_price' => $request->precio_compra ?? 0,
                'expiry_date' => $request->expiry_date,
                'status' => 'active'
            ]);
        }

        return response()->json(['message' => 'Producto registrado con éxito', 'product' => $product]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $user->role !== 'employee') {
            return response()->json(['error' => 'No tienes permisos para editar productos.'], 403);
        }

        $product = Product::findOrFail($id);
        $product->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'precio_venta' => $request->precio_venta,
            'points_reward' => $request->points_reward,
            'points_cost' => $request->points_cost,
            'max_quota_per_user' => $request->max_quota_per_user,
            'image_url' => $request->image_url,
            'parent_id' => $request->parent_id ?? $product->parent_id,
            'package_multiplier' => $request->package_multiplier ?? $product->package_multiplier,
        ]);

        return response()->json(['message' => 'Producto actualizado', 'product' => $product]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $user->role !== 'employee') {
            return response()->json(['error' => 'No tienes permisos para borrar productos.'], 403);
        }

        Product::destroy($id);
        return response()->json(['message' => 'Producto eliminado']);
    }
}

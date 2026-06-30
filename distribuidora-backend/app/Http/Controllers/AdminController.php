<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Category;
use App\Models\Supplier;

class AdminController extends Controller
{
    public function index()
    {
        $users = \App\Models\User::whereIn('role', ['admin', 'employee', 'delivery'])->with('persona')->get();
        return response()->json($users);
    }

    public function registerEmployee(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'apellidos' => 'required|string|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.'
        ]);

        $persona = \App\Models\Persona::create([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
        ]);

        $user = \App\Models\User::create([
            'persona_id' => $persona->id,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role' => $request->has('role') ? $request->role : 'employee',
        ]);

        return response()->json(['message' => 'Empleado registrado', 'user' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);
        
        $request->validate([
            'nombre' => 'required|string|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'apellidos' => 'required|string|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'email' => 'required|email|unique:users,email,'.$id,
            'role' => 'required|in:admin,employee,delivery'
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.'
        ]);

        $user->email = $request->email;
        $user->role = $request->role;
        $user->save();

        if ($user->persona) {
            $user->persona->nombre = $request->nombre;
            $user->persona->apellidos = $request->apellidos;
            $user->persona->save();
        }

        return response()->json(['message' => 'Empleado actualizado']);
    }

    public function destroy($id)
    {
        $user = \App\Models\User::findOrFail($id);
        $user->delete(); // Soft delete o delete
        return response()->json(['message' => 'Empleado eliminado']);
    }

    public function getCategories()
    {
        return response()->json(Category::all());
    }

    public function addCategory(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        $cat = Category::create(['name' => $request->name]);
        return response()->json(['message' => 'Categoría creada', 'category' => $cat]);
    }

    public function updateCategory(Request $request, $id)
    {
        $request->validate(['name' => 'required|string']);
        $cat = Category::findOrFail($id);
        $cat->update(['name' => $request->name]);
        return response()->json(['message' => 'Categoría actualizada', 'category' => $cat]);
    }

    public function deleteCategory($id)
    {
        $cat = Category::findOrFail($id);
        $cat->delete();
        return response()->json(['message' => 'Categoría eliminada']);
    }

    public function getSuppliers()
    {
        return response()->json(Supplier::all());
    }

    public function addSupplier(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'contact_info' => 'nullable|string',
            'nit' => 'nullable|string'
        ]);

        $supplier = Supplier::create($request->all());
        return response()->json(['message' => 'Proveedor creado', 'supplier' => $supplier]);
    }

    public function updateSupplier(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'contact_info' => 'nullable|string',
            'nit' => 'nullable|string'
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->all());
        return response()->json(['message' => 'Proveedor actualizado', 'supplier' => $supplier]);
    }

    public function deleteSupplier($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();
        return response()->json(['message' => 'Proveedor eliminado']);
    }

    public function addBatch(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date'
        ]);

        $batch = ProductBatch::create([
            'product_id' => $request->product_id,
            'supplier_id' => $request->supplier_id,
            'quantity_initial' => $request->quantity,
            'quantity_current' => $request->quantity,
            'purchase_price' => $request->purchase_price,
            'expiry_date' => $request->expiry_date,
            'status' => 'active'
        ]);

        $product = Product::find($request->product_id);
        $product->stock += $request->quantity;
        $product->save();

        return response()->json(['message' => 'Stock ingresado correctamente', 'batch' => $batch]);
    }

    public function getExpiringBatches()
    {
        // Traer lotes que están activos y expiran en menos de 4 semanas
        $threeWeeksFromNow = now()->addWeeks(4);
        
        $batches = ProductBatch::with('product')
            ->whereIn('status', ['active', 'promotion'])
            ->whereNotNull('expiry_date')
            ->where('quantity_current', '>', 0)
            ->whereDate('expiry_date', '<=', $threeWeeksFromNow)
            ->orderBy('expiry_date', 'asc')
            ->get();

        return response()->json($batches);
    }

    public function updateBatchStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,expired,exhausted,promotion',
            'discount_price' => 'nullable|numeric|min:0'
        ]);

        $batch = ProductBatch::findOrFail($id);
        $batch->status = $request->status;
        if ($request->has('discount_price')) {
            $batch->discount_price = $request->discount_price;
        }
        $batch->save();

        // Si se marca como expirado, se descuenta del stock general del producto
        if ($request->status === 'expired') {
            $product = $batch->product;
            $product->stock -= $batch->quantity_current;
            $product->save();
        }

        return response()->json(['message' => 'Lote actualizado correctamente']);
    }

    public function getReports(Request $request)
    {
        $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        // 1. Ventas por fecha
        $sales = \App\Models\Order::whereIn('status', ['delivered', 'completed'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 2. Productos más vendidos
        $products = \App\Models\OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', ['delivered', 'completed'])
            ->whereDate('orders.created_at', '>=', $startDate)
            ->whereDate('orders.created_at', '<=', $endDate)
            ->selectRaw('products.name as product, SUM(order_items.quantity) as total_quantity')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        // 3. Nuevos clientes por fecha
        $users = \App\Models\User::where('role', 'client')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->selectRaw('DATE(created_at) as date, COUNT(id) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 4. Lista detallada de clientes
        $clients_list = \App\Models\User::where('role', 'client')
            ->join('personas', 'users.persona_id', '=', 'personas.id')
            ->whereDate('users.created_at', '>=', $startDate)
            ->whereDate('users.created_at', '<=', $endDate)
            ->orderBy('personas.apellidos')
            ->select('users.id', 'users.email', 'users.loyalty_points', 'users.created_at', 'personas.nombre', 'personas.apellidos', 'personas.telefono')
            ->get();

        // 5. Ventas detalladas para Excel Completo
        $detailed_sales = \App\Models\OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('order_item_batches', 'order_item_batches.order_item_id', '=', 'order_items.id')
            ->leftJoin('product_batches', 'product_batches.id', '=', 'order_item_batches.product_batch_id')
            ->leftJoin('users as client', 'client.id', '=', 'orders.user_id')
            ->leftJoin('personas as client_persona', 'client_persona.id', '=', 'client.persona_id')
            ->leftJoin('users as delivery', 'delivery.id', '=', 'orders.delivery_user_id')
            ->leftJoin('personas as delivery_persona', 'delivery_persona.id', '=', 'delivery.persona_id')
            ->whereIn('orders.status', ['delivered', 'completed', 'in_transit']) // Include in_transit just in case, but delivered is safer. Let's keep delivered and completed.
            ->whereDate('orders.created_at', '>=', $startDate)
            ->whereDate('orders.created_at', '<=', $endDate)
            ->select(
                'orders.id as pedido_id',
                'orders.created_at as fecha',
                \DB::raw("CONCAT(COALESCE(client_persona.nombre,''), ' ', COALESCE(client_persona.apellidos,'')) as cliente"),
                'products.name as producto',
                \DB::raw('COALESCE(order_item_batches.quantity, order_items.quantity) as cantidad'),
                'order_items.price_at_time as precio_venta',
                'product_batches.purchase_price as precio_compra',
                'orders.payment_method as metodo_pago',
                'orders.order_type as tipo_pedido',
                'orders.delivery_cost as delivery_cost',
                \DB::raw("CONCAT(COALESCE(delivery_persona.nombre,''), ' ', COALESCE(delivery_persona.apellidos,'')) as empleado_delivery")
            )
            ->orderBy('orders.created_at', 'asc')
            ->get();

        return response()->json([
            'sales' => $sales,
            'products' => $products,
            'users' => $users,
            'clients_list' => $clients_list,
            'detailed_sales' => $detailed_sales
        ]);
    }

    public function toggleTimeLimit(Request $request)
    {
        $current = \Illuminate\Support\Facades\Cache::get('enforce_time_limit', true);
        \Illuminate\Support\Facades\Cache::put('enforce_time_limit', !$current);
        return response()->json([
            'message' => 'Límite de horario actualizado',
            'enforce_time_limit' => !$current
        ]);
    }

    public function getTimeLimit()
    {
        return response()->json([
            'enforce_time_limit' => \Illuminate\Support\Facades\Cache::get('enforce_time_limit', true)
        ]);
    }
    
    public function searchClients(Request $request)
    {
        $q = $request->query('q', '');
        $query = \App\Models\User::where('role', 'client')->with('persona');

        if ($q) {
            $query->whereHas('persona', function ($queryBuilder) use ($q) {
                $queryBuilder->where('nombre', 'like', "%{$q}%")
                             ->orWhere('apellidos', 'like', "%{$q}%")
                             ->orWhere('ci_nit', 'like', "%{$q}%");
            })->orWhere('email', 'like', "%{$q}%");
        }

        return response()->json($query->take(20)->get());
    }
}

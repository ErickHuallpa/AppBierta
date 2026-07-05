<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PaymentAdminController;
use App\Http\Controllers\DeliveryController;

// Autenticación y Registro
Route::get('/system/check', [AuthController::class, 'systemCheck']);
Route::post('/register/admin', [AuthController::class, 'registerAdmin']);
Route::post('/register/client', [AuthController::class, 'registerClient']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas Públicas (Catálogo)
Route::get('/products', function () {
    return \App\Models\Product::all();
});
Route::get('/categories', [\App\Http\Controllers\AdminController::class, 'getCategories']);

// Rutas Protegidas por Autenticación
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load('persona');
    });
    
    // Rutas de cliente
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'myOrders']);
    Route::post('/orders/{id}/rate', [OrderController::class, 'rateDelivery']);

    // Perfil
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'updatePassword']);

    // Compras (Clientes)
    Route::post('/orders/attempt', [OrderController::class, 'attemptOrder']);
    Route::post('/orders/confirm', [OrderController::class, 'store']);
    Route::get('/my-orders', [OrderController::class, 'myOrders']);
    Route::get('/my-purchased-products', [OrderController::class, 'myPurchasedProducts']);
    Route::post('/rewards/redeem', [RewardController::class, 'redeem']);

    // Administración (Empleados)
    Route::get('/employees', [AdminController::class, 'index']);
    Route::post('/register/employee', [AdminController::class, 'registerEmployee']);
    Route::put('/employees/{id}', [AdminController::class, 'update']);
    Route::delete('/employees/{id}', [AdminController::class, 'destroy']);
    
    // Productos (CRUD)
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Ubicaciones
    Route::get('/locations', [LocationController::class, 'index']);
    Route::post('/locations', [LocationController::class, 'store']);
    Route::put('/locations/{id}', [LocationController::class, 'update']);
    Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
    Route::post('/locations/{id}/set-default', [LocationController::class, 'setDefault']);

    // Admin Pagos
    Route::get('/admin/payments', [PaymentAdminController::class, 'pendingPayments']);
    Route::post('/admin/payments/{id}/approve', [PaymentAdminController::class, 'approve']);
    Route::post('/admin/payments/{id}/reject', [PaymentAdminController::class, 'reject']);

    // Delivery
    Route::get('/delivery/orders/available', [DeliveryController::class, 'availableOrders']);
    Route::get('/delivery/my-orders', [DeliveryController::class, 'myDeliveries']);
    Route::get('/delivery/orders/{id}', [DeliveryController::class, 'show']);
    Route::post('/delivery/orders/{id}/accept', [DeliveryController::class, 'acceptOrder']);
    Route::post('/delivery/orders/{id}/delivered', [DeliveryController::class, 'markAsDelivered']);

    // Admin Routes (Lotes, Proveedores, Categorías)
    // Categorías admin
    Route::post('/categories', [\App\Http\Controllers\AdminController::class, 'addCategory']);
    Route::put('/categories/{id}', [\App\Http\Controllers\AdminController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [\App\Http\Controllers\AdminController::class, 'deleteCategory']);

    Route::get('/suppliers', [\App\Http\Controllers\AdminController::class, 'getSuppliers']);
    Route::post('/suppliers', [\App\Http\Controllers\AdminController::class, 'addSupplier']);
    Route::put('/suppliers/{id}', [\App\Http\Controllers\AdminController::class, 'updateSupplier']);
    Route::delete('/suppliers/{id}', [\App\Http\Controllers\AdminController::class, 'deleteSupplier']);
    Route::post('/batches', [\App\Http\Controllers\AdminController::class, 'addBatch']);
    Route::get('/batches/expiring', [\App\Http\Controllers\AdminController::class, 'getExpiringBatches']);
    Route::get('/admin/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard']);
    Route::get('/admin/pickup-orders', [\App\Http\Controllers\AdminController::class, 'pickupOrders']);
    Route::post('/admin/orders/{id}/deliver', [\App\Http\Controllers\AdminController::class, 'markDelivered']);
    
    // Rutas de Control de Lotes (Batches)
    Route::put('/batches/{id}/status', [\App\Http\Controllers\AdminController::class, 'updateBatchStatus']);
    Route::get('/reports', [\App\Http\Controllers\AdminController::class, 'getReports']);

    // Configuración
    Route::get('/settings/time-limit', [\App\Http\Controllers\AdminController::class, 'getTimeLimit']);
    Route::post('/settings/time-limit/toggle', [\App\Http\Controllers\AdminController::class, 'toggleTimeLimit']);
    Route::get('/pos/clients', [\App\Http\Controllers\AdminController::class, 'searchClients']);
});

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear un Persona y Usuario de prueba
        $persona = \App\Models\Persona::create([
            'nombre' => 'Juan',
            'apellidos' => 'Perez',
            'telefono' => '77712345',
            'direccion' => 'Av. Principal 123'
        ]);

        $user = \App\Models\User::create([
            'persona_id' => $persona->id,
            'email' => 'cliente@test.com',
            'password' => bcrypt('password'),
            'role' => 'client',
            'loyalty_points' => 0,
            'delivery_route_day' => now()->dayOfWeekIso // Le toca ruta hoy para probar envíos gratis
        ]);

        // 2. Crear Productos (Cervezas)
        \App\Models\Product::create([
            'name' => 'Paceña Lata 354ml (Pack de 6)',
            'presentation' => 'lata',
            'precio_compra' => 45.00,
            'precio_venta' => 60.00,
            'stock' => 100,
            'points_reward' => 100,
            'points_cost' => 1500, // Se necesitan 1500 puntos para canjear un pack gratis
            'max_quota_per_user' => 5 // Solo pueden comprar 5 packs
        ]);

        \App\Models\Product::create([
            'name' => 'Huari Botella 620ml (Caja de 12)',
            'presentation' => 'botella',
            'precio_compra' => 130.00,
            'precio_venta' => 160.00,
            'stock' => 50,
            'points_reward' => 250,
            'points_cost' => 3500,
            'max_quota_per_user' => 2 // Cupo más estricto
        ]);

        \App\Models\Product::create([
            'name' => 'Taquiña Botella 620ml',
            'presentation' => 'botella',
            'precio_compra' => 10.00,
            'precio_venta' => 15.00,
            'stock' => 200,
            'points_reward' => 15,
            'points_cost' => 250,
            'max_quota_per_user' => 24
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $baseProducts = Product::whereNull('parent_id')->get();

        foreach ($baseProducts as $product) {
            $packages = [
                ['name' => 'Six-Pack', 'multiplier' => 6, 'discount' => 0.95],
                ['name' => 'Pack de 9', 'multiplier' => 9, 'discount' => 0.90],
                ['name' => 'Caja de 12', 'multiplier' => 12, 'discount' => 0.90],
                ['name' => 'Caja de 24', 'multiplier' => 24, 'discount' => 0.85],
            ];

            foreach ($packages as $pkg) {
                // Verificar si el paquete ya existe para no duplicar
                $exists = Product::where('parent_id', $product->id)
                    ->where('package_multiplier', $pkg['multiplier'])
                    ->exists();

                if (!$exists) {
                    Product::create([
                        'name' => $pkg['name'] . ' ' . $product->name,
                        'category_id' => $product->category_id,
                        'precio_venta' => round($product->precio_venta * $pkg['multiplier'] * $pkg['discount'], 2),
                        'stock' => 0, // El stock se maneja por el producto base o por lotes.
                        'points_reward' => $product->points_reward * $pkg['multiplier'],
                        'points_cost' => $product->points_cost * $pkg['multiplier'],
                        'max_quota_per_user' => null,
                        'image_url' => $product->image_url,
                        'parent_id' => $product->id,
                        'package_multiplier' => $pkg['multiplier'],
                    ]);
                }
            }
        }
    }
}

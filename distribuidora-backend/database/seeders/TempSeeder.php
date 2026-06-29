<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;

class TempSeeder extends Seeder
{
    public function run(): void
    {
        $c1 = Category::create(['name' => 'Lata 354ml', 'image_url' => 'lata.png']);
        $c2 = Category::create(['name' => 'Botella 1L', 'image_url' => 'botella.png']);
        $c3 = Category::create(['name' => 'Fardos', 'image_url' => 'fardo.png']);
        
        foreach (Product::all() as $p) {
            $p->category_id = $c1->id;
            $p->save();
            
            ProductBatch::create([
                'product_id' => $p->id,
                'quantity_initial' => $p->stock,
                'quantity_current' => $p->stock,
                'purchase_price' => 15,
                'status' => 'active',
                'expiry_date' => now()->addMonths(6)
            ]);
        }
    }
}

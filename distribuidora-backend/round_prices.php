<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach(\App\Models\Product::whereNotNull('parent_id')->get() as $p) {
    $p->precio_venta = round($p->precio_venta);
    $p->save();
}
echo "Done\n";

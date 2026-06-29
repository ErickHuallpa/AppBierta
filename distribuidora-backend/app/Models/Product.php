<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'precio_venta',
        'stock',
        'points_reward',
        'points_cost',
        'max_quota_per_user',
        'image_url',
        'parent_id',
        'package_multiplier'
    ];

    protected $appends = ['real_stock'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function packages()
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    public function getRealStockAttribute()
    {
        if ($this->parent_id && $this->parent) {
            // It's a package. Calculate based on parent's real stock
            return floor($this->parent->real_stock / $this->package_multiplier);
        }
        return $this->stock; // Base product stock
    }
}

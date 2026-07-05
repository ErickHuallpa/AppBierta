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

    protected $appends = ['real_stock', 'last_purchase_price', 'promotional_price', 'promotional_stock'];

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

    public function getLastPurchasePriceAttribute()
    {
        $lastBatch = $this->batches()->latest()->first();
        return $lastBatch ? $lastBatch->purchase_price : 0;
    }

    public function getPromotionalPriceAttribute()
    {
        $baseProduct = $this->parent_id && $this->parent ? $this->parent : $this;
        $promoBatch = $baseProduct->batches()->where('status', 'promotion')->where('quantity_current', '>', 0)->first();
        if ($promoBatch) {
            return round(($this->precio_venta * 0.8) * 2) / 2;
        }
        return null;
    }

    public function getPromotionalStockAttribute()
    {
        $baseProduct = $this->parent_id && $this->parent ? $this->parent : $this;
        $promoBatch = $baseProduct->batches()->where('status', 'promotion')->where('quantity_current', '>', 0)->first();
        if ($promoBatch) {
            if ($this->parent_id) {
                 return floor($promoBatch->quantity_current / $this->package_multiplier);
            }
            return $promoBatch->quantity_current;
        }
        return 0;
    }
}

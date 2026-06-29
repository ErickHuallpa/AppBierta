<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total_amount',
        'delivery_cost',
        'status',
        'rating',
        'is_point_exchange',
        'payment_method',
        'payment_receipt',
        'payment_status',
        'location_id',
        'delivery_user_id',
        'order_type',
        'notes',
        'nit',
        'razon_social'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function deliveryUser()
    {
        return $this->belongsTo(User::class, 'delivery_user_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}

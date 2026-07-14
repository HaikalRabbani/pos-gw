<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDiscount extends Model
{
    protected $table = 'order_discount';

    protected $fillable = [
        'order_id', 'discount_id', 'discount_name',
        'discount_type', 'discount_value', 'discount_amount',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }
}

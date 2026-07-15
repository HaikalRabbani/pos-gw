<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'product_id', 'variant_id',
        'product_name', 'variant_name', 'qty',
        'unit_price', 'unit_cost', 'total_price', 'notes',
    ];

    protected $appends = ['refundable_qty'];

    protected function casts(): array
    {
        return [
            'refunded_qty' => 'integer',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getRefundableQtyAttribute(): int
    {
        return max($this->qty - ($this->refunded_qty ?? 0), 0);
    }
}

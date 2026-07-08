<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'outlet_id', 'table_id', 'user_id', 'customer_name',
        'status', 'subtotal', 'discount_total', 'tax_total', 'grand_total',
        'payment_status', 'payment_method', 'notes', 'bill_group_id',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function table()
    {
        return $this->belongsTo(RestTable::class, 'table_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function discounts()
    {
        return $this->hasMany(OrderDiscount::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function logs()
    {
        return $this->hasMany(OrderLog::class);
    }
}

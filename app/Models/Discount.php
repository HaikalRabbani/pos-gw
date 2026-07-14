<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = [
        'outlet_id', 'name', 'type', 'value', 'is_active',
        'target_type', 'target_id', 'min_purchase', 'max_discount',
        'buy_x', 'buy_y', 'start_date', 'end_date',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_purchase' => 'integer',
            'max_discount' => 'integer',
            'buy_x' => 'integer',
            'buy_y' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function targetProduct()
    {
        return $this->belongsTo(Product::class, 'target_id');
    }

    public function targetCategory()
    {
        return $this->belongsTo(Category::class, 'target_id');
    }
}

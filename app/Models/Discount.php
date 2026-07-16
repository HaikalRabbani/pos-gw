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
            'target_id' => 'array',
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

    /**
     * Get target products for frontend display.
     * Works with both single ID (legacy) and array of IDs.
     */
    public function getTargetProductsAttribute()
    {
        if ($this->target_type !== 'product' || empty($this->target_id)) {
            return [];
        }
        $ids = is_array($this->target_id) ? $this->target_id : [$this->target_id];
        return Product::whereIn('id', $ids)->get(['id', 'name'])->toArray();
    }

    /**
     * Get target categories for frontend display.
     * Works with both single ID (legacy) and array of IDs.
     */
    public function getTargetCategoriesAttribute()
    {
        if ($this->target_type !== 'category' || empty($this->target_id)) {
            return [];
        }
        $ids = is_array($this->target_id) ? $this->target_id : [$this->target_id];
        return Category::whereIn('id', $ids)->get(['id', 'name'])->toArray();
    }
}

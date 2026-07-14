<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'name', 'price_extra', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = ['outlet_id', 'name', 'is_active', 'sort_order', 'stock'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'stock' => 'integer',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')
            ->withPivot(['is_removable', 'extra_price', 'is_default', 'sort_order'])
            ->withTimestamps();
    }
}

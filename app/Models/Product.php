<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['outlet_id', 'category_id', 'station_id', 'name', 'description', 'price', 'cost', 'stock', 'min_stock', 'image', 'is_active', 'sort_order'];

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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
            ->withPivot(['is_removable', 'extra_price', 'is_default', 'sort_order'])
            ->orderBy('pivot_sort_order')
            ->withTimestamps();
    }

    public function removableIngredients()
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
            ->wherePivot('is_removable', true)
            ->withPivot(['extra_price', 'is_default', 'sort_order'])
            ->orderBy('pivot_sort_order');
    }

    public function addons()
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
            ->wherePivot('extra_price', '>', 0)
            ->withPivot(['extra_price', 'is_default', 'sort_order'])
            ->orderBy('pivot_sort_order');
    }
}

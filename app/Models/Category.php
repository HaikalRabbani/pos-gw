<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['outlet_id', 'name', 'sort_order'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

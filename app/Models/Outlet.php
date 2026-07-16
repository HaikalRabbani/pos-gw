<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outlet extends Model
{
    use HasFactory;
    protected $fillable = ['tenant_id', 'name', 'address', 'phone', 'is_active', 'midtrans_server_key'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'midtrans_server_key' => 'encrypted',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_outlets')
            ->withPivot('role');
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function taxes()
    {
        return $this->hasMany(Tax::class);
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    public function tables()
    {
        return $this->hasMany(RestTable::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
}

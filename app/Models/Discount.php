<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = ['outlet_id', 'name', 'type', 'value', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}

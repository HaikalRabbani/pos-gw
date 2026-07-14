<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $fillable = ['outlet_id', 'name', 'rate', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}

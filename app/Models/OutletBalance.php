<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletBalance extends Model
{
    protected $fillable = ['outlet_id', 'balance', 'total_withdrawn'];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'total_withdrawn' => 'integer',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}

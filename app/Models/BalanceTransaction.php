<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceTransaction extends Model
{
    protected $fillable = [
        'outlet_id', 'type', 'amount', 'description',
        'reference_type', 'reference_id',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}

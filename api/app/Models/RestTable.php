<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestTable extends Model
{
    protected $table = 'tables';

    protected $fillable = ['outlet_id', 'name', 'qr_token', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    protected $fillable = ['order_id', 'from_status', 'to_status', 'user_id', 'note'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

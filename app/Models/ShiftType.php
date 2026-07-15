<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftType extends Model
{
    protected $fillable = [
        'outlet_id',
        'name',
        'start_time',
        'end_time',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'string',
            'end_time' => 'string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function schedules()
    {
        return $this->hasMany(ShiftSchedule::class);
    }
}

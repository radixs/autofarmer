<?php

namespace App\Models\Cache;

use Illuminate\Database\Eloquent\Model;

abstract class MeasurementCache extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'range_start_at',
        'range_end_at',
        'name',
        'unit',
        'value_avg',
        'value_min',
        'value_max',
        'source',
        'updated_at',
    ];

    protected $casts = [
        'range_start_at' => 'datetime',
        'range_end_at' => 'datetime',
        'updated_at' => 'datetime',
        'value_avg' => 'float',
        'value_min' => 'float',
        'value_max' => 'float',
    ];
}

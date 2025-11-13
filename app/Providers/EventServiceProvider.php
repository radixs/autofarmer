<?php

namespace App\Providers;

use App\Events\MeasurementCached;
use App\Events\MeasurementStored;
use App\Listeners\BroadcastMeasurementCached;
use App\Listeners\UpdateMeasurementCaches;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MeasurementStored::class => [
            UpdateMeasurementCaches::class,
        ],
        MeasurementCached::class => [
            BroadcastMeasurementCached::class,
        ],
    ];
}

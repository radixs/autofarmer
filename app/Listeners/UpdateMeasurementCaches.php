<?php

namespace App\Listeners;

use App\Events\MeasurementStored;
use App\Services\CachingService;

class UpdateMeasurementCaches
{
    public function __construct(private readonly CachingService $cachingService)
    {
    }

    public function handle(MeasurementStored $event): void
    {
        $this->cachingService->processMeasurement($event->measurement);
    }
}

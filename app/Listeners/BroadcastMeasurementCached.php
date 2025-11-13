<?php

namespace App\Listeners;

use App\Events\MeasurementCached;
use App\Events\MeasurementUpdatePushed;
use App\Services\MeasurementsService;
use App\Support\MeasurementSubscriptionStore;

class BroadcastMeasurementCached
{
    public function __construct(
        private readonly MeasurementSubscriptionStore $subscriptionStore,
        private readonly MeasurementsService $measurementsService,
    ) {
    }

    public function handle(MeasurementCached $event): void
    {
        foreach ($this->subscriptionStore->all() as $subscriptionId => $filters) {
            $response = $this->measurementsService->getMeasurements($filters, rememberFilters: false, ensureCacheFreshness: false);

            broadcast(new MeasurementUpdatePushed(
                $subscriptionId,
                $response->toArray(),
                $event->ranges
            ));
        }
    }
}

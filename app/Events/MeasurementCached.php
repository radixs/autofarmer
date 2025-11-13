<?php

namespace App\Events;

use App\Enums\MeasurementInterval;
use App\Models\Measurement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeasurementCached
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<string, array{is_new: bool, interval: MeasurementInterval, cache_id: int}> $ranges
     */
    public function __construct(
        public readonly Measurement $measurement,
        public readonly array $ranges = []
    ) {
    }
}

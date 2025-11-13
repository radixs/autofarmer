<?php

namespace App\Events;

use App\Models\Measurement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeasurementStored
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Measurement $measurement)
    {
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeasurementUpdatePushed implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $subscriptionId,
        public readonly array $payload,
        public readonly array $ranges,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('measurements.' . $this->subscriptionId);
    }

    public function broadcastAs(): string
    {
        return 'measurement.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'subscriptionId' => $this->subscriptionId,
            'payload' => $this->payload,
            'ranges' => $this->ranges,
        ];
    }
}

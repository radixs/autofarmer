<?php

namespace App\DataTransferObjects;

use App\Enums\MeasurementInterval;

class MeasurementResponseData
{
    /**
     * @param array{
     *     interval: string,
     *     data: array<int, MeasurementHistoryRowData>,
     *     meta: array<string, mixed>
     * } $history
     */
    public function __construct(
        public readonly array $currentMeasurements,
        public readonly array $history,
        public readonly string $subscriptionId,
    ) {
    }

    public function toArray(): array
    {
        return [
            'currentMeasurements' => array_map(fn (CurrentMeasurementData $item) => $item->toArray(), $this->currentMeasurements),
            'measurementHistory' => [
                'interval' => $this->history['interval'] ?? '',
                'data' => array_map(
                    fn (MeasurementHistoryRowData $row) => $row->toArray(),
                    $this->history['data'] ?? []
                ),
                'meta' => $this->history['meta'] ?? [],
            ],
            'subscriptionId' => $this->subscriptionId,
        ];
    }
}

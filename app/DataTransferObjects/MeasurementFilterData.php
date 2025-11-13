<?php

namespace App\DataTransferObjects;

use App\Enums\MeasurementInterval;
use Carbon\CarbonImmutable;

class MeasurementFilterData
{
    public function __construct(
        public readonly ?CarbonImmutable $dateFrom = null,
        public readonly ?CarbonImmutable $dateTo = null,
        public readonly array $names = [],
        public readonly MeasurementInterval $interval = MeasurementInterval::HOURLY,
        public readonly int $perPage = 20,
        public readonly int $page = 1,
        public readonly ?string $subscriptionId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $from = isset($data['date_from']) ? CarbonImmutable::parse($data['date_from']) : null;
        $to = isset($data['date_to']) ? CarbonImmutable::parse($data['date_to']) : null;
        $names = array_values(array_filter((array)($data['names'] ?? [])));
        $interval = MeasurementInterval::from($data['interval'] ?? MeasurementInterval::HOURLY->value);
        $perPage = (int)($data['per_page'] ?? config('measurements.default_per_page', 20));
        $page = (int)($data['page'] ?? 1);
        $subscriptionId = $data['subscription_id'] ?? null;

        return new self($from, $to, $names, $interval, $perPage, $page, $subscriptionId);
    }

    public function toCachePayload(): array
    {
        return [
            'date_from' => $this->dateFrom?->toDateTimeString(),
            'date_to' => $this->dateTo?->toDateTimeString(),
            'names' => $this->names,
            'interval' => $this->interval->value,
            'per_page' => $this->perPage,
            'page' => $this->page,
        ];
    }
}

<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;

class MeasurementHistoryRowData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $unit,
        public readonly float $valueAvg,
        public readonly float $valueMin,
        public readonly float $valueMax,
        public readonly ?string $source,
        public readonly CarbonImmutable $rangeStart,
        public readonly CarbonImmutable $rangeEnd,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'],
            unit: $data['unit'],
            valueAvg: (float) $data['value_avg'],
            valueMin: (float) $data['value_min'],
            valueMax: (float) $data['value_max'],
            source: $data['source'] ?? null,
            rangeStart: CarbonImmutable::parse($data['range_start_at']),
            rangeEnd: CarbonImmutable::parse($data['range_end_at']),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->unit,
            'value_avg' => $this->valueAvg,
            'value_min' => $this->valueMin,
            'value_max' => $this->valueMax,
            'source' => $this->source,
            'range_start_at' => $this->rangeStart->toDateTimeString(),
            'range_end_at' => $this->rangeEnd->toDateTimeString(),
        ];
    }
}

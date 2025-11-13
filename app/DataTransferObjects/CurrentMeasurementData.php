<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;

class CurrentMeasurementData
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $unit,
        public readonly float $value,
        public readonly string $source,
        public readonly CarbonImmutable $createdAt,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            name: $payload['name'],
            label: $payload['label'],
            unit: $payload['unit'],
            value: (float) $payload['value'],
            source: $payload['source'],
            createdAt: CarbonImmutable::parse($payload['created_at']),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'unit' => $this->unit,
            'value' => $this->value,
            'source' => $this->source,
            'created_at' => $this->createdAt->toDateTimeString(),
        ];
    }
}

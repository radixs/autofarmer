<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;

class MeasurementStoreData
{
    public function __construct(
        public readonly string $name,
        public readonly string $unit,
        public readonly string $source,
        public readonly float $value,
        public readonly CarbonImmutable $createdAt,
    ) {
    }

    public static function fromArray(array $data, string $unit): self
    {
        return new self(
            name: $data['name'],
            unit: $unit,
            source: $data['source'],
            value: (float) $data['value'],
            createdAt: isset($data['created_at'])
                ? CarbonImmutable::parse($data['created_at'], 'UTC')
                : CarbonImmutable::now('UTC'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'unit' => $this->unit,
            'source' => $this->source,
            'value' => $this->value,
            'created_at' => $this->createdAt->toDateTimeString(),
        ];
    }
}

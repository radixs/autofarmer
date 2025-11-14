<?php

namespace App\Services;

use App\Support\SensorMode;

class SensorModeService
{
    public function __construct(private readonly SensorMode $store)
    {
    }

    public function status(): array
    {
        return $this->formatResponse($this->store->isEnabled());
    }

    public function setState(bool $enabled): array
    {
        $this->store->setEnabled($enabled);

        return $this->formatResponse($enabled);
    }

    private function formatResponse(bool $enabled): array
    {
        return [
            'enabled' => $enabled,
            'state' => $enabled ? 'on' : 'off',
        ];
    }
}

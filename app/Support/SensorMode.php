<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Facades\File;

class SensorMode
{
    private const STATE_FILE = 'sensor_mode.json';
    private const DEFAULT_ENABLED = false;

    private ?bool $state = null;

    public function isEnabled(): bool
    {
        if ($this->state === null) {
            $this->state = $this->readState()['enabled'] ?? self::DEFAULT_ENABLED;
        }

        return $this->state;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->state = $enabled;
        $this->writeState([
            'enabled' => $enabled,
            'updated_at' => now('UTC')->format(DateTimeInterface::ATOM),
        ]);
    }

    public function statePath(): string
    {
        $relativeDirectory = app()->runningUnitTests()
            ? 'app/testing'
            : 'app';

        return storage_path($relativeDirectory.'/'.self::STATE_FILE);
    }

    private function readState(): array
    {
        $path = $this->statePath();

        if (! File::exists($path)) {
            return ['enabled' => self::DEFAULT_ENABLED];
        }

        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded) || ! array_key_exists('enabled', $decoded)) {
            return ['enabled' => self::DEFAULT_ENABLED];
        }

        return $decoded;
    }

    private function writeState(array $payload): void
    {
        $path = $this->statePath();

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT));
    }
}

<?php

namespace App\Console\Commands;

use App\Services\SensorModeService;
use Illuminate\Console\Command;

class SensorModeCommand extends Command
{
    protected $signature = 'sensor:set {state : Accepts "on" or "off"}';

    protected $description = 'Toggle whether sensor sourced payloads are processed.';

    public function __construct(private readonly SensorModeService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $state = strtolower((string) $this->argument('state'));

        if (! in_array($state, ['on', 'off'], true)) {
            $this->error('State must be either "on" or "off".');

            return Command::FAILURE;
        }

        $enabled = $state === 'on';
        $payload = $this->service->setState($enabled);

        $this->info('Sensor ingestion is now '.strtoupper($payload['state']).'.');

        return Command::SUCCESS;
    }
}

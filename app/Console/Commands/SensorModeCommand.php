<?php

namespace App\Console\Commands;

use App\Support\SensorMode;
use Illuminate\Console\Command;

class SensorModeCommand extends Command
{
    protected $signature = 'sensor:set {state : Accepts "on" or "off"}';

    protected $description = 'Toggle whether sensor sourced payloads are processed.';

    public function __construct(private readonly SensorMode $sensorMode)
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
        $this->sensorMode->setEnabled($enabled);

        $this->info('Sensor ingestion is now '.($enabled ? 'ON' : 'OFF').'.');

        return Command::SUCCESS;
    }
}

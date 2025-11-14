<?php

namespace App\Console\Commands;

use App\Support\TelemetryTables;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetTelemetryCommand extends Command
{
    protected $signature = 'measurements:reset';

    protected $description = 'Truncate the measurement and cache tables without repopulating them.';

    public function handle(): int
    {
        Schema::disableForeignKeyConstraints();

        foreach (TelemetryTables::names() as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();

        $this->info('Telemetry tables truncated: '.implode(', ', TelemetryTables::names()).'.');

        return Command::SUCCESS;
    }
}

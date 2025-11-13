<?php

namespace App\Console\Commands;

use App\Models\Measurement;
use App\Services\CachingService;
use App\Support\MeasurementDictionary;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetMeasurements extends Command
{
    protected $signature = 'measurements:seed {--days=30 : Number of days of history to generate}';

    protected $description = 'Reset measurement and cache tables with realistic aquarium data.';

    public function __construct(private readonly CachingService $cachingService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $definitions = MeasurementDictionary::all();

        DB::table('measurements')->truncate();
        DB::table('hourly_caches')->truncate();
        DB::table('daily_caches')->truncate();
        DB::table('weekly_caches')->truncate();

        $start = CarbonImmutable::now('UTC')->subDays($days)->startOfDay();
        $end = CarbonImmutable::now('UTC');
        $total = 0;

        for ($timestamp = $start; $timestamp->lte($end); $timestamp = $timestamp->addHour()) {
            foreach ($definitions as $slug => $definition) {
                $value = $this->generateValue($definition['range'] ?? null, $timestamp);

                $measurement = Measurement::create([
                    'name' => $slug,
                    'unit' => $definition['unit'] ?? '',
                    'source' => $timestamp->hour % 6 === 0 ? 'manual' : 'sensor',
                    'value' => $value,
                    'created_at' => $timestamp,
                ]);

                $this->cachingService->processMeasurement($measurement, broadcast: false);
                $total++;
            }
        }

        $this->info("Seeded {$total} measurements across {$days} days.");

        return Command::SUCCESS;
    }

    private function generateValue(?array $range, CarbonImmutable $timestamp): float
    {
        if (! $range) {
            return random_int(1, 100);
        }

        $min = (float) $range['min'];
        $max = (float) $range['max'];
        $baseline = ($min + $max) / 2;
        $variance = ($max - $min) / 6 ?: 0.5;
        $noise = sin($timestamp->timestamp / 3600) * $variance;

        $value = $baseline + $noise + mt_rand(-100, 100) / 1000;

        return round(max($min - $variance, min($max + $variance, $value)), 3);
    }
}

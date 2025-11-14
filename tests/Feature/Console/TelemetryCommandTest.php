<?php

namespace Tests\Feature\Console;

use App\Models\Cache\HourlyMeasurementCache;
use App\Models\Measurement;
use App\Support\SensorMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TelemetryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_command_writes_sql_dump(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-11-01 13:12:12', 'UTC'));
        Measurement::factory()->forSlug('ph')->manual()->withValue(7.22)->create();

        HourlyMeasurementCache::query()->create([
            'name' => 'ph',
            'unit' => 'pH',
            'value_avg' => 7.2,
            'value_min' => 7.1,
            'value_max' => 7.3,
            'source' => 'sensor',
            'range_start_at' => Carbon::now('UTC')->subHour(),
            'range_end_at' => Carbon::now('UTC'),
            'updated_at' => Carbon::now('UTC'),
        ]);

        $path = storage_path('db_backups/db_2025-11-01_-13-12-12.sql');

        if (File::exists($path)) {
            File::delete($path);
        }

        $this->artisan('measurements:backup')->assertExitCode(0);

        $this->assertFileExists($path);

        $contents = File::get($path);
        $this->assertStringContainsString('REPLACE INTO `measurements`', $contents);
        $this->assertStringContainsString('REPLACE INTO `hourly_caches`', $contents);

        File::delete($path);
        Carbon::setTestNow();
    }

    public function test_restore_command_merges_rows_by_default(): void
    {
        $existing = Measurement::factory()->forSlug('ph')->withValue(7.1)->create();
        $second = Measurement::factory()->forSlug('temp')->withValue(18.3)->create();

        $sql = sprintf(
            "REPLACE INTO `measurements` (`id`, `name`, `unit`, `source`, `value`, `created_at`, `updated_at`) VALUES (%d, 'ph', 'pH', 'sensor', 9.87, '2024-01-01 00:00:00', '2024-01-01 00:00:00');\n",
            $existing->id
        );

        $path = storage_path('db_backups/test_restore.sql');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $sql);

        $this->artisan('measurements:restore', ['path' => 'test_restore.sql'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('measurements', [
            'id' => $existing->id,
            'value' => 9.87,
        ]);

        $this->assertDatabaseHas('measurements', [
            'id' => $second->id,
            'value' => $second->value,
        ]);

        File::delete($path);
    }

    public function test_restore_command_truncates_tables_when_merge_is_disabled(): void
    {
        Measurement::factory()->count(2)->create();

        $sql = "REPLACE INTO `measurements` (`id`, `name`, `unit`, `source`, `value`, `created_at`, `updated_at`) VALUES (999, 'ph', 'pH', 'manual', 6.42, '2024-02-01 00:00:00', '2024-02-01 00:00:00');\n";
        $path = storage_path('db_backups/test_restore_truncate.sql');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $sql);

        $this->artisan('measurements:restore', [
            'path' => $path,
            '--merge' => 'false',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('measurements', ['id' => 999, 'value' => 6.42]);
        $this->assertSame(1, Measurement::count());

        File::delete($path);
    }

    public function test_sensor_command_persists_mode_across_toggles(): void
    {
        /** @var SensorMode $sensorMode */
        $sensorMode = app(SensorMode::class);
        $this->assertFalse($sensorMode->isEnabled());

        $this->artisan('sensor:set', ['state' => 'on'])->assertExitCode(0);
        $this->assertTrue(app(SensorMode::class)->isEnabled());

        $this->artisan('sensor:set', ['state' => 'off'])->assertExitCode(0);
        $this->assertFalse(app(SensorMode::class)->isEnabled());
    }

    public function test_sensor_mode_defaults_to_off_when_state_file_missing(): void
    {
        $sensorMode = app(SensorMode::class);
        $path = $sensorMode->statePath();

        if (file_exists($path)) {
            unlink($path);
        }

        $this->assertFalse($sensorMode->isEnabled());
    }
}

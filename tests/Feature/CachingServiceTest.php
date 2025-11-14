<?php

namespace Tests\Feature;

use App\Events\MeasurementCached;
use App\Models\Cache\DailyMeasurementCache;
use App\Models\Cache\HourlyMeasurementCache;
use App\Models\Cache\WeeklyMeasurementCache;
use App\Models\Measurement;
use App\Services\CachingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CachingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_cache_ranges_and_fires_events_for_new_measurements(): void
    {
        // Arrange: create a single measurement inside a known timestamp bucket.
        $measurement = Measurement::factory()->forSlug('ph')->sensor()->withValue(7.11)->create([
            'created_at' => CarbonImmutable::parse('2024-04-10 14:23:00', 'UTC'),
            'updated_at' => CarbonImmutable::parse('2024-04-10 14:23:00', 'UTC'),
        ]);
        Event::fake([MeasurementCached::class]);

        // Act: process the measurement through the caching service.
        app(CachingService::class)->processMeasurement($measurement);

        // Assert: each cache table now has a row seeded from the measurement.
        $this->assertDatabaseCount('hourly_caches', 1);
        $this->assertDatabaseCount('daily_caches', 1);
        $this->assertDatabaseCount('weekly_caches', 1);

        Event::assertDispatched(MeasurementCached::class, function ($event) use ($measurement) {
            return $event->measurement->is($measurement)
                && collect($event->ranges)->keys()->sort()->values()->all() === ['daily', 'hourly', 'weekly']
                && collect($event->ranges)->every(fn ($range) => $range['is_new'] === true);
        });
    }

    public function test_it_updates_existing_ranges_with_recalculated_statistics(): void
    {
        // Arrange: seed an initial reading so caches already exist.
        Event::fake([MeasurementCached::class]);
        $first = Measurement::factory()->forSlug('ph')->sensor()->withValue(7.00)->create([
            'created_at' => CarbonImmutable::parse('2024-04-11 09:10:00', 'UTC'),
            'updated_at' => CarbonImmutable::parse('2024-04-11 09:10:00', 'UTC'),
        ]);
        app(CachingService::class)->processMeasurement($first);

        // Arrange: insert a second reading in the same calendar range to trigger an update.
        $second = Measurement::factory()->forSlug('ph')->manual()->withValue(7.50)->create([
            'created_at' => CarbonImmutable::parse('2024-04-11 09:40:00', 'UTC'),
            'updated_at' => CarbonImmutable::parse('2024-04-11 09:40:00', 'UTC'),
        ]);

        // Act: process the new reading so the cache rows recalc min/max/avg.
        app(CachingService::class)->processMeasurement($second);

        // Assert: the cached stats now reflect both readings and were treated as updates.
        $hourly = HourlyMeasurementCache::firstWhere('name', 'ph');
        $this->assertSame(7.25, $hourly->value_avg);
        $this->assertSame(7.00, $hourly->value_min);
        $this->assertSame(7.50, $hourly->value_max);

        $daily = DailyMeasurementCache::firstWhere('name', 'ph');
        $this->assertSame(7.25, $daily->value_avg);
        $weekly = WeeklyMeasurementCache::firstWhere('name', 'ph');
        $this->assertSame(7.25, $weekly->value_avg);

        Event::assertDispatched(MeasurementCached::class, function ($event) use ($second) {
            return $event->measurement->is($second)
                && collect($event->ranges)->every(fn ($range) => $range['is_new'] === false);
        });
    }

    public function test_backfill_missing_populates_cache_tables_from_historical_measurements(): void
    {
        // Arrange: create a backlog of measurements without any cache rows.
        Measurement::factory()->forSlug('ph')->withValue(7.00)->create([
            'created_at' => CarbonImmutable::parse('2024-04-09 06:15:00', 'UTC'),
        ]);
        Measurement::factory()->forSlug('ph')->withValue(7.40)->create([
            'created_at' => CarbonImmutable::parse('2024-04-09 06:45:00', 'UTC'),
        ]);

        // Act: run the backfill to hydrate caches from existing measurements.
        app(CachingService::class)->backfillMissing();

        // Assert: cache tables receive aggregates even without real-time events.
        $this->assertDatabaseCount('hourly_caches', 1);
        $this->assertSame(7.20, HourlyMeasurementCache::first()->value_avg);
        $this->assertDatabaseCount('daily_caches', 1);
        $this->assertDatabaseCount('weekly_caches', 1);
    }
}

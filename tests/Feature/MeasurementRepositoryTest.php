<?php

namespace Tests\Feature;

use App\DataTransferObjects\MeasurementFilterData;
use App\Enums\MeasurementInterval;
use App\Models\Cache\HourlyMeasurementCache;
use App\Models\Measurement;
use App\Repositories\MeasurementRepository;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_latest_per_type_returns_single_row_per_series(): void
    {
        // Arrange: create multiple entries for each measurement type.
        $repo = app(MeasurementRepository::class);
        $latestPh = Measurement::factory()->forSlug('ph')->withValue(7.3)->create([
            'created_at' => CarbonImmutable::parse('2024-04-12 11:15:00', 'UTC'),
        ]);
        Measurement::factory()->forSlug('ph')->withValue(7.0)->create([
            'created_at' => CarbonImmutable::parse('2024-04-11 08:00:00', 'UTC'),
        ]);
        $latestTemp = Measurement::factory()->forSlug('temp')->withValue(19.2)->create([
            'created_at' => CarbonImmutable::parse('2024-04-12 09:00:00', 'UTC'),
        ]);

        // Act: fetch the latest entry per measurement slug.
        $results = $repo->fetchLatestPerType();

        // Assert: the freshest reading for each name is returned.
        $this->assertCount(2, $results);
        $this->assertTrue($results->firstWhere('name', 'ph')->is($latestPh));
        $this->assertTrue($results->firstWhere('name', 'temp')->is($latestTemp));
    }

    public function test_fetch_history_applies_date_and_name_filters(): void
    {
        // Arrange: seed two cached rows where one falls outside the requested window.
        $repo = app(MeasurementRepository::class);
        $rangeIn = CarbonImmutable::parse('2024-05-01 08:00:00', 'UTC');
        $rangeOut = CarbonImmutable::parse('2024-04-01 08:00:00', 'UTC');

        HourlyMeasurementCache::query()->create([
            'name' => 'ph',
            'unit' => 'pH',
            'value_avg' => 7.1,
            'value_min' => 7.0,
            'value_max' => 7.2,
            'source' => 'sensor',
            'range_start_at' => $rangeIn,
            'range_end_at' => $rangeIn->endOfHour(),
            'updated_at' => $rangeIn->addMinutes(15),
        ]);

        HourlyMeasurementCache::query()->create([
            'name' => 'ph',
            'unit' => 'pH',
            'value_avg' => 6.9,
            'value_min' => 6.8,
            'value_max' => 7.0,
            'source' => 'manual',
            'range_start_at' => $rangeOut,
            'range_end_at' => $rangeOut->endOfHour(),
            'updated_at' => $rangeOut->addMinutes(15),
        ]);

        $filters = MeasurementFilterData::fromArray([
            'date_from' => '2024-05-01 00:00:00',
            'date_to' => '2024-05-02 23:59:59',
            'interval' => 'hourly',
            'names' => ['ph'],
            'per_page' => 10,
        ]);

        // Act: query the history paginator.
        $paginator = $repo->fetchHistory($filters, MeasurementInterval::HOURLY);

        // Assert: only the matching row is returned and meta reflects totals.
        $this->assertSame(1, $paginator->total());
        $this->assertSame('ph', $paginator->items()[0]->name);
        $this->assertSame(7.1, (float) $paginator->items()[0]->value_avg);
    }

    public function test_fetch_measurements_for_range_returns_collection_in_bounds(): void
    {
        // Arrange: seed readings right inside and outside of the requested window.
        $repo = app(MeasurementRepository::class);
        $inside = Measurement::factory()->forSlug('ph')->withValue(7.05)->create([
            'created_at' => CarbonImmutable::parse('2024-05-03 09:10:00', 'UTC'),
        ]);
        Measurement::factory()->forSlug('ph')->withValue(7.60)->create([
            'created_at' => CarbonImmutable::parse('2024-05-03 12:00:00', 'UTC'),
        ]);

        // Act: fetch the readings within the 9-10am window.
        $window = $repo->fetchMeasurementsForRange(
            'ph',
            CarbonImmutable::parse('2024-05-03 09:00:00', 'UTC'),
            CarbonImmutable::parse('2024-05-03 10:00:00', 'UTC')
        );

        // Assert: only the expected reading is returned.
        $this->assertCount(1, $window);
        $this->assertTrue($window->first()->is($inside));
    }
}

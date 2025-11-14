<?php

namespace Tests\Api;

use App\Events\MeasurementStored;
use App\Models\Cache\HourlyMeasurementCache;
use App\Models\Measurement;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MeasurementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_measurement_dashboard_payload(): void
    {
        // Arrange: seed latest measurements and cached history rows.
        $now = CarbonImmutable::parse('2024-05-01 10:00:00', 'UTC');
        Measurement::factory()->forSlug('ph')->sensor()->withValue(7.15)->create([
            'created_at' => $now->subMinutes(30),
            'updated_at' => $now->subMinutes(30),
        ]);
        Measurement::factory()->forSlug('temp')->manual()->withValue(19.8)->create([
            'created_at' => $now->subMinutes(45),
            'updated_at' => $now->subMinutes(45),
        ]);

        HourlyMeasurementCache::query()->create([
            'name' => 'ph',
            'unit' => 'pH',
            'value_avg' => 7.05,
            'value_min' => 6.95,
            'value_max' => 7.15,
            'source' => 'sensor',
            'range_start_at' => $now->subHours(2)->startOfHour(),
            'range_end_at' => $now->subHours(2)->endOfHour(),
            'updated_at' => $now->subHour(),
        ]);

        HourlyMeasurementCache::query()->create([
            'name' => 'ph',
            'unit' => 'pH',
            'value_avg' => 7.10,
            'value_min' => 7.00,
            'value_max' => 7.20,
            'source' => 'manual',
            'range_start_at' => $now->subHour()->startOfHour(),
            'range_end_at' => $now->subHour()->endOfHour(),
            'updated_at' => $now->subMinutes(30),
        ]);

        // Act: request hourly history filtered to the pH series.
        $response = $this->getJson('/api/measurements?' . http_build_query([
            'interval' => 'hourly',
            'names' => ['ph'],
            'per_page' => 5,
        ]));

        // Assert: dashboard payload includes current measurements, paged history, and a subscription id.
        $response->assertOk();
        $response->assertJsonPath('measurementHistory.interval', 'hourly');
        $response->assertJsonPath('measurementHistory.meta.per_page', 5);
        $response->assertJsonPath('measurementHistory.meta.total', 2);
        $this->assertNotEmpty($response->json('subscriptionId'));

        $current = collect($response->json('currentMeasurements'));
        $this->assertSame(7.15, (float) $current->firstWhere('name', 'ph')['value']);
        $history = collect($response->json('measurementHistory.data'));
        $this->assertCount(2, $history);
        $this->assertSame(7.10, (float) $history->first()['value_avg']);
    }

    public function test_it_normalizes_and_persists_measurements_via_api(): void
    {
        // Arrange: fake events to capture MeasurementStored dispatch.
        Event::fake([MeasurementStored::class]);

        // Act: submit a manual reading using the human label casing.
        $response = $this->postJson('/api/measurements', [
            'name' => 'pH',
            'value' => 7.22,
            'source' => 'manual',
        ]);

        // Assert: API stores normalized slug + unit and emits the MeasurementStored event.
        $response->assertCreated();
        $response->assertJsonPath('data.name', 'ph');
        $this->assertDatabaseHas('measurements', [
            'name' => 'ph',
            'unit' => 'pH',
            'source' => 'manual',
        ]);

        Event::assertDispatched(MeasurementStored::class, function ($event) {
            return $event->measurement->name === 'ph'
                && $event->measurement->unit === 'pH';
        });
    }
}

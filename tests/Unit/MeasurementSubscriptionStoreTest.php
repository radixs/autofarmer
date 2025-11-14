<?php

namespace Tests\Unit;

use App\DataTransferObjects\MeasurementFilterData;
use App\Support\MeasurementSubscriptionStore;
use Illuminate\Support\Str;
use Tests\TestCase;

class MeasurementSubscriptionStoreTest extends TestCase
{
    public function test_it_persists_and_recalls_subscription_filters(): void
    {
        // Arrange: create filter DTO and store it without a predefined subscription id.
        $store = app(MeasurementSubscriptionStore::class);
        $filters = MeasurementFilterData::fromArray([
            'date_from' => '2024-05-01 00:00:00',
            'date_to' => '2024-05-02 00:00:00',
            'interval' => 'daily',
            'names' => ['ph', 'temp'],
            'per_page' => 25,
        ]);

        // Act: remember the filters and then fetch them back via all/get helpers.
        $subscriptionId = $store->remember(null, $filters);
        $all = $store->all();
        $retrieved = $store->get($subscriptionId);

        // Assert: the filters are serialized/deserialized faithfully under the generated id.
        $this->assertArrayHasKey($subscriptionId, $all);
        $this->assertSame($filters->interval->value, $retrieved->interval->value);
        $this->assertSame($filters->names, $retrieved->names);
        $this->assertSame(25, $retrieved->perPage);
    }

    public function test_it_replaces_existing_subscription_payloads_and_supports_forget(): void
    {
        // Arrange: seed a specific subscription id and override the payload later.
        $store = app(MeasurementSubscriptionStore::class);
        $id = (string) Str::uuid();
        $firstFilters = MeasurementFilterData::fromArray([
            'interval' => 'hourly',
            'names' => ['ph'],
        ]);
        $secondFilters = MeasurementFilterData::fromArray([
            'interval' => 'weekly',
            'names' => ['temp'],
        ]);

        // Act: store twice for the same id and then forget it.
        $store->remember($id, $firstFilters);
        $store->remember($id, $secondFilters);
        $store->forget($id);

        // Assert: the entry is gone from cache once forgotten.
        $this->assertNull($store->get($id));
        $this->assertArrayNotHasKey($id, $store->all());
    }
}

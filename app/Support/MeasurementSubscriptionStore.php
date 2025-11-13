<?php

namespace App\Support;

use App\DataTransferObjects\MeasurementFilterData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MeasurementSubscriptionStore
{
    private const CACHE_KEY = 'measurement_subscriptions';

    public function remember(?string $subscriptionId, MeasurementFilterData $filters): string
    {
        $subscriptions = $this->allRaw();
        $id = $subscriptionId ?: (string) Str::uuid();

        $subscriptions[$id] = [
            'filters' => $filters->toCachePayload(),
            'updated_at' => now()->toDateTimeString(),
        ];

        Cache::put(self::CACHE_KEY, $subscriptions, now()->addDay());

        return $id;
    }

    public function all(): array
    {
        $items = [];

        foreach ($this->allRaw() as $id => $payload) {
            $items[$id] = MeasurementFilterData::fromArray([
                ...$payload['filters'],
                'subscription_id' => $id,
            ]);
        }

        return $items;
    }

    public function get(string $subscriptionId): ?MeasurementFilterData
    {
        $payload = $this->allRaw()[$subscriptionId] ?? null;

        if (! $payload) {
            return null;
        }

        return MeasurementFilterData::fromArray([
            ...$payload['filters'],
            'subscription_id' => $subscriptionId,
        ]);
    }

    public function forget(string $subscriptionId): void
    {
        $subscriptions = $this->allRaw();
        unset($subscriptions[$subscriptionId]);
        Cache::put(self::CACHE_KEY, $subscriptions, now()->addDay());
    }

    private function allRaw(): array
    {
        return Cache::get(self::CACHE_KEY, []);
    }
}

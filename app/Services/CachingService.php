<?php

namespace App\Services;

use App\Enums\MeasurementInterval;
use App\Events\MeasurementCached;
use App\Models\{Measurement, HourlyMeasurementCache, DailyMeasurementCache, WeeklyMeasurementCache};
use App\Repositories\MeasurementRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class CachingService
{
    public function __construct(private readonly MeasurementRepository $repository)
    {
    }

    public function processMeasurement(Measurement $measurement, bool $broadcast = true): void
    {
        $ranges = [];

        foreach (MeasurementInterval::cases() as $interval) {
            [$cache, $isNew] = $this->upsertCache($interval, $measurement);

            $ranges[$interval->value] = [
                'is_new' => $isNew,
                'interval' => $interval,
                'cache_id' => $cache->id,
            ];
        }

        if ($broadcast) {
            MeasurementCached::dispatch($measurement, $ranges);
        }
    }

    public function backfillMissing(): void
    {
        $latestMeasurementAt = Measurement::query()->max('created_at');

        if (! $latestMeasurementAt) {
            return;
        }

        foreach (MeasurementInterval::cases() as $interval) {
            /** @var HourlyMeasurementCache|DailyMeasurementCache|WeeklyMeasurementCache $modelClass */
            $modelClass = $interval->modelClass();
            $lastCacheEnd = $modelClass::query()->max('range_end_at');

            if ($lastCacheEnd && CarbonImmutable::parse($lastCacheEnd)->greaterThanOrEqualTo(CarbonImmutable::parse($latestMeasurementAt))) {
                continue;
            }

            $query = Measurement::query()
                ->when($lastCacheEnd, function ($builder) use ($lastCacheEnd) {
                    return $builder->where('created_at', '>=', CarbonImmutable::parse($lastCacheEnd)->subHour()->toDateTimeString());
                })
                ->orderBy('created_at');

            foreach ($query->cursor() as $measurement) {
                $this->upsertCache($interval, $measurement);
            }
        }
    }

    /**
     * @return array{Model, bool}
     */
    private function upsertCache(MeasurementInterval $interval, Measurement $measurement): array
    {
        /** @var HourlyMeasurementCache|DailyMeasurementCache|WeeklyMeasurementCache $modelClass */
        $modelClass = $interval->modelClass();
        [$rangeStart, $rangeEnd] = $this->resolveRange($interval, CarbonImmutable::parse($measurement->created_at));

        /** @var Model $cache */
        $cache = $modelClass::query()->firstOrNew([
            'name' => $measurement->name,
            'range_start_at' => $rangeStart->toDateTimeString(),
        ]);

        $isNew = ! $cache->exists;

        if ($isNew) {
            $cache->fill([
                'range_start_at' => $rangeStart,
                'range_end_at' => $rangeEnd,
                'unit' => $measurement->unit,
                'value_avg' => $measurement->value,
                'value_min' => $measurement->value,
                'value_max' => $measurement->value,
                'source' => $measurement->source,
                'updated_at' => now(),
            ])->save();

            return [$cache, true];
        }

        $stats = $this->repository->fetchMeasurementsForRange(
            $measurement->name,
            $rangeStart,
            $rangeEnd
        );

        $cache->fill([
            'value_avg' => round($stats->avg('value'), 5),
            'value_min' => (float) $stats->min('value'),
            'value_max' => (float) $stats->max('value'),
            'source' => $measurement->source,
            'updated_at' => now(),
        ])->save();

        return [$cache, false];
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function resolveRange(MeasurementInterval $interval, string $createdAt): array
    {
        $date = CarbonImmutable::parse($createdAt);

        $date = $date->setTimezone('UTC');

        return match ($interval) {
            MeasurementInterval::HOURLY => [
                $date->startOfHour(),
                $date->startOfHour()->endOfHour(),
            ],
            MeasurementInterval::DAILY => [
                $date->startOfDay(),
                $date->endOfDay(),
            ],
            MeasurementInterval::WEEKLY => [
                $date->startOfWeek(),
                $date->endOfWeek(),
            ],
        };
    }
}

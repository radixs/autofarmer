<?php

namespace App\Enums;

use App\Models\Cache\DailyMeasurementCache;
use App\Models\Cache\HourlyMeasurementCache;
use App\Models\Cache\MeasurementCache;
use App\Models\Cache\WeeklyMeasurementCache;

enum MeasurementInterval: string
{
    case HOURLY = 'hourly';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';

    public function table(): string
    {
        return match ($this) {
            self::HOURLY => 'hourly_caches',
            self::DAILY => 'daily_caches',
            self::WEEKLY => 'weekly_caches',
        };
    }

    /**
     * @return class-string<MeasurementCache>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::HOURLY => HourlyMeasurementCache::class,
            self::DAILY => DailyMeasurementCache::class,
            self::WEEKLY => WeeklyMeasurementCache::class,
        };
    }
}

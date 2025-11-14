<?php

namespace App\Support;

class TelemetryTables
{
    /**
     * Tables that store measurement history and their caches.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return [
            'measurements',
            'hourly_caches',
            'daily_caches',
            'weekly_caches',
        ];
    }
}

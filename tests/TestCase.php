<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $paths = [
            storage_path('app/testing/sensor_mode.json'),
            storage_path('app/sensor_mode.json'),
        ];

        foreach ($paths as $sensorState) {
            if (file_exists($sensorState)) {
                unlink($sensorState);
            }
        }
    }
}

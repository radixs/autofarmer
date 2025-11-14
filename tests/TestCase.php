<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $sensorState = storage_path('app/testing/sensor_mode.json');

        if (file_exists($sensorState)) {
            unlink($sensorState);
        }
    }
}

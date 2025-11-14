<?php

namespace Tests\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensorModeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_current_sensor_state(): void
    {
        $response = $this->getJson('/api/sensor-mode');

        $response->assertOk();
        $response->assertJsonPath('data.enabled', false);
        $response->assertJsonPath('data.state', 'off');
    }

    public function test_it_updates_sensor_state(): void
    {
        $response = $this->putJson('/api/sensor-mode', ['enabled' => true]);
        $response->assertOk();
        $response->assertJsonPath('data.enabled', true);
        $response->assertJsonPath('data.state', 'on');

        $this->getJson('/api/sensor-mode')
            ->assertOk()
            ->assertJsonPath('data.enabled', true);
    }

    public function test_it_validates_state_payload(): void
    {
        $this->putJson('/api/sensor-mode', ['enabled' => 'invalid'])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('enabled');
    }
}

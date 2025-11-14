<?php

namespace Database\Factories;

use App\Models\Measurement;
use App\Support\MeasurementDictionary;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Measurement>
 */
class MeasurementFactory extends Factory
{
    protected $model = Measurement::class;

    protected static int $sequence = 1;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $slug = array_key_first(config('measurements.types', [])) ?? 'ph';
        $unit = MeasurementDictionary::resolveUnit($slug) ?: 'unit';
        $sequence = self::$sequence++;
        $timestamp = CarbonImmutable::parse('2024-01-01 00:00:00', 'UTC')->addMinutes($sequence);
        $value = round(6 + ($sequence * 0.05), 3);
        $source = $sequence % 2 === 0 ? 'manual' : 'sensor';

        return [
            'name' => $slug,
            'unit' => $unit,
            'source' => $source,
            'value' => $value,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    public function forSlug(string $slug): static
    {
        return $this->state(fn () => [
            'name' => $slug,
            'unit' => MeasurementDictionary::resolveUnit($slug) ?: 'unit',
        ]);
    }

    public function withValue(float $value): static
    {
        return $this->state(fn () => ['value' => $value]);
    }

    public function manual(): static
    {
        return $this->state(fn () => ['source' => 'manual']);
    }

    public function sensor(): static
    {
        return $this->state(fn () => ['source' => 'sensor']);
    }
}

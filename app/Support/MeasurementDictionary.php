<?php

namespace App\Support;

use Illuminate\Support\Arr;

class MeasurementDictionary
{
    public static function all(): array
    {
        return config('measurements.types', []);
    }

    public static function labels(): array
    {
        return array_map(fn ($definition) => $definition['label'], self::all());
    }

    public static function bySlug(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    public static function resolveUnit(string $slug): string
    {
        return self::bySlug($slug)['unit'] ?? '';
    }

    public static function normalizeName(string $name): ?string
    {
        $needle = mb_strtolower($name);

        foreach (self::all() as $slug => $definition) {
            $labels = array_filter([
                $definition['label'] ?? null,
                $definition['ascii_label'] ?? null,
                $slug,
            ]);

            foreach ($labels as $label) {
                if ($needle === mb_strtolower($label)) {
                    return $slug;
                }
            }
        }

        return null;
    }
}

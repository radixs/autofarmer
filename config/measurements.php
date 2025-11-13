<?php

$path = resource_path('data/measurement_ranges.json');
$definitions = [];

if (file_exists($path)) {
    $decoded = json_decode(file_get_contents($path), true);
    if (is_array($decoded)) {
        $definitions = $decoded;
    }
}

$types = [];

foreach ($definitions as $definition) {
    if (! isset($definition['slug'])) {
        continue;
    }

    $types[$definition['slug']] = [
        'label' => $definition['label'] ?? ucfirst($definition['slug']),
        'ascii_label' => $definition['asciiLabel'] ?? ($definition['label'] ?? $definition['slug']),
        'unit' => $definition['unit'] ?? '',
        'range' => $definition['range'] ?? null,
    ];
}

return [
    'types' => $types,
    'default_per_page' => 20,
];

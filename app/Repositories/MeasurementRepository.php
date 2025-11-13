<?php

namespace App\Repositories;

use App\DataTransferObjects\MeasurementFilterData;
use App\DataTransferObjects\MeasurementStoreData;
use App\Enums\MeasurementInterval;
use App\Models\Measurement;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MeasurementRepository
{
    public function store(MeasurementStoreData $data): Measurement
    {
        return Measurement::create([
            'name' => $data->name,
            'unit' => $data->unit,
            'source' => $data->source,
            'value' => $data->value,
            'created_at' => $data->createdAt->toDateTimeString(),
        ]);
    }

    public function fetchLatestPerType(): Collection
    {
        $sub = Measurement::query()
            ->select('*', DB::raw('ROW_NUMBER() OVER (PARTITION BY name ORDER BY created_at DESC, id DESC) AS rn'));

        return Measurement::query()
            ->fromSub($sub, 'ranked_measurements')
            ->where('rn', 1)
            ->orderBy('name')
            ->get();
    }

    public function fetchHistory(MeasurementFilterData $filters, MeasurementInterval $interval): LengthAwarePaginator
    {
        $query = DB::table($interval->table());

        if ($filters->dateFrom) {
            $query->where('range_end_at', '>=', $filters->dateFrom->toDateTimeString());
        }

        if ($filters->dateTo) {
            $query->where('range_start_at', '<=', $filters->dateTo->toDateTimeString());
        }

        if (! empty($filters->names)) {
            $query->whereIn('name', $filters->names);
        }

        return $query
            ->orderByDesc('range_start_at')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page)
            ->withQueryString();
    }

    public function fetchMeasurementsForRange(string $name, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return Measurement::query()
            ->where('name', $name)
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->get();
    }
}

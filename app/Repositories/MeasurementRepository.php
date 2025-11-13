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
        $subQuery = Measurement::select('name', DB::raw('MAX(created_at) as max_created_at'))
            ->groupBy('name');

        return Measurement::joinSub($subQuery, 'latest_measurements', function ($join) {
            $join->on('measurements.name', '=', 'latest_measurements.name');
            $join->on('measurements.created_at', '=', 'latest_measurements.max_created_at');
        })->orderBy('measurements.name')->get();
    }

    public function fetchHistory(MeasurementFilterData $filters, MeasurementInterval $interval): LengthAwarePaginator
    {
        $query = DB::table($interval->table());

        if ($filters->dateFrom) {
            $query->where('range_start_at', '>=', $filters->dateFrom->toDateTimeString());
        }

        if ($filters->dateTo) {
            $query->where('range_end_at', '<=', $filters->dateTo->toDateTimeString());
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

<?php

namespace App\Services;

use App\DataTransferObjects\CurrentMeasurementData;
use App\DataTransferObjects\MeasurementFilterData;
use App\DataTransferObjects\MeasurementHistoryRowData;
use App\DataTransferObjects\MeasurementResponseData;
use App\DataTransferObjects\MeasurementStoreData;
use App\Enums\MeasurementInterval;
use App\Events\MeasurementStored;
use App\Models\Measurement;
use App\Repositories\MeasurementRepository;
use App\Services\CachingService;
use App\Support\MeasurementDictionary;
use App\Support\MeasurementSubscriptionStore;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MeasurementsService
{
    public function __construct(
        private readonly MeasurementRepository $repository,
        private readonly MeasurementSubscriptionStore $subscriptionStore,
        private readonly CachingService $cachingService,
    ) {
    }

    public function getMeasurements(MeasurementFilterData $filters, bool $rememberFilters = true, bool $ensureCacheFreshness = true): MeasurementResponseData
    {
        if ($ensureCacheFreshness) {
            $this->cachingService->backfillMissing();
        }

        $subscriptionId = $filters->subscriptionId;

        if ($rememberFilters) {
            $subscriptionId = $this->subscriptionStore->remember($filters->subscriptionId, $filters);
        }

        $currentMeasurements = $this->repository
            ->fetchLatestPerType()
            ->map(function (Measurement $measurement) {
                $definition = MeasurementDictionary::bySlug($measurement->name);

                return new CurrentMeasurementData(
                    name: $measurement->name,
                    label: $definition['label'] ?? $measurement->name,
                    unit: $measurement->unit,
                    value: (float) $measurement->value,
                    source: $measurement->source,
                    createdAt: $measurement->created_at,
                );
            })->all();

        $history = [];

        $history = $this->formatPaginator(
            $filters->interval,
            $this->repository->fetchHistory($filters, $filters->interval)
        );

        return new MeasurementResponseData(
            currentMeasurements: $currentMeasurements,
            history: $history,
            subscriptionId: $subscriptionId ?? '',
        );
    }

    public function storeMeasurement(array $payload): Measurement
    {
        $slug = MeasurementDictionary::normalizeName($payload['name']);

        if (! $slug) {
            throw ValidationException::withMessages([
                'name' => __('Unknown measurement name.'),
            ]);
        }

        $unit = MeasurementDictionary::resolveUnit($slug);

        $storeData = MeasurementStoreData::fromArray([
            'name' => $slug,
            'source' => $payload['source'],
            'value' => $payload['value'],
            'created_at' => $payload['created_at'] ?? now()->toDateTimeString(),
        ], $unit);

        $measurement = $this->repository->store($storeData);

        MeasurementStored::dispatch($measurement);

        return $measurement;
    }

    private function formatPaginator(MeasurementInterval $interval, LengthAwarePaginator $paginator): array
    {
        return [
            'interval' => $interval->value,
            'data' => array_map(function ($row) {
                return MeasurementHistoryRowData::fromArray((array) $row);
            }, $paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}

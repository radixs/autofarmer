<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeasurementFiltersRequest;
use App\Http\Requests\MeasurementStoreRequest;
use App\Services\MeasurementsService;
use Illuminate\Http\JsonResponse;

class MeasurementsController extends Controller
{
    public function __construct(private readonly MeasurementsService $service)
    {
    }

    public function index(MeasurementFiltersRequest $request): JsonResponse
    {
        $response = $this->service->getMeasurements($request->toDto());

        return response()->json($response->toArray());
    }

    public function store(MeasurementStoreRequest $request): JsonResponse
    {
        $measurement = $this->service->storeMeasurement($request->payload());

        if (! $measurement) {
            return response()->json([
                'notice' => 'Receiver in off mode. Sensor payload skipped.',
            ], 201);
        }

        return response()->json([
            'data' => $measurement->only(['id', 'name', 'unit', 'source', 'value', 'created_at']),
        ], 201);
    }
}

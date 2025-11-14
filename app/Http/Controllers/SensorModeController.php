<?php

namespace App\Http\Controllers;

use App\Http\Requests\SensorModeUpdateRequest;
use App\Services\SensorModeService;
use Illuminate\Http\JsonResponse;

class SensorModeController extends Controller
{
    public function __construct(private readonly SensorModeService $service)
    {
    }

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->status(),
        ]);
    }

    public function update(SensorModeUpdateRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->service->setState($request->boolean('enabled')),
        ]);
    }
}

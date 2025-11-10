<?php

namespace App\Http\Controllers;

use App\Models\TestEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestEntryController extends Controller
{
    public function index(): JsonResponse
    {
        $entries = TestEntry::query()
            ->latest()
            ->get();

        return response()->json(['data' => $entries]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'payload' => ['required', 'array'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $entry = TestEntry::create([
            'title' => $validated['title'],
            'payload' => $validated['payload'],
            'source' => $validated['source'] ?? 'api',
            'status' => $validated['status'] ?? 'new',
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        return response()->json(['data' => $entry], 201);
    }
}

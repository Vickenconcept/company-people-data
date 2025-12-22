<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiKeyController extends Controller
{
    /**
     * Get all API keys for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $apiKeys = ApiKey::where('user_id', $request->user()->id)
            ->select('id', 'service', 'is_active', 'created_at', 'updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $apiKeys,
        ]);
    }

    /**
     * Store a new API key
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service' => 'required|string|in:openai,scraperapi,apollo,hunter',
            'api_key' => 'required|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $apiKey = ApiKey::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'service' => $request->service,
            ],
            [
                'api_key' => $request->api_key,
                'is_active' => $request->is_active ?? true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'API key stored successfully',
            'data' => [
                'id' => $apiKey->id,
                'service' => $apiKey->service,
                'is_active' => $apiKey->is_active,
            ],
        ], 201);
    }

    /**
     * Update an API key
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $apiKey = ApiKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'api_key' => 'sometimes|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $apiKey->update($request->only(['api_key', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'API key updated successfully',
            'data' => [
                'id' => $apiKey->id,
                'service' => $apiKey->service,
                'is_active' => $apiKey->is_active,
            ],
        ]);
    }

    /**
     * Delete an API key
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $apiKey = ApiKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => 'API key deleted successfully',
        ]);
    }
}

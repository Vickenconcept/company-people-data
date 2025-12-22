<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessLeadDiscovery;
use App\Models\LeadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadGenController extends Controller
{
    /**
     * Create a new lead generation request
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference_company_name' => 'required|string|max:255',
            'reference_company_url' => 'nullable|url|max:500',
            'target_count' => 'nullable|integer|min:1|max:100',
            'target_job_titles' => 'nullable|array',
            'target_job_titles.*' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $leadRequest = LeadRequest::create([
            'user_id' => $request->user()->id,
            'reference_company_name' => $request->reference_company_name,
            'reference_company_url' => $request->reference_company_url,
            'target_count' => $request->target_count ?? 10,
            'target_job_titles' => $request->target_job_titles ?? ['CEO', 'CFO'],
            'status' => 'pending',
        ]);

        // Dispatch job to process lead discovery
        ProcessLeadDiscovery::dispatch($leadRequest);

        return response()->json([
            'success' => true,
            'message' => 'Lead generation request created successfully',
            'data' => [
                'id' => $leadRequest->id,
                'status' => $leadRequest->status,
            ],
        ], 201);
    }

    /**
     * Get all lead requests for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $leadRequests = LeadRequest::where('user_id', $request->user()->id)
            ->with(['leadResults.company', 'leadResults.person'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $leadRequests,
        ]);
    }

    /**
     * Get a specific lead request
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $leadRequest = LeadRequest::where('user_id', $request->user()->id)
            ->with(['leadResults.company', 'leadResults.person'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $leadRequest,
        ]);
    }

    /**
     * Get lead results for a specific request
     */
    public function results(Request $request, int $id): JsonResponse
    {
        $leadRequest = LeadRequest::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $results = $leadRequest->leadResults()
            ->with(['company', 'person'])
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\PeopleSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PeopleSearchController extends Controller
{
    /**
     * Search for people in a company
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'job_titles' => 'required|array|min:1',
            'job_titles.*' => 'string|max:100',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = Company::findOrFail($request->company_id);

            $peopleSearchService = new PeopleSearchService();
            $peopleSearchService->setApiKeyFromUser($request->user(), 'apollo');

            $result = $peopleSearchService->findPeople(
                $company,
                $request->job_titles,
                $request->limit ?? 10
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to search people',
                ], 400);
            }

            // Store people
            $people = $peopleSearchService->storePeople($result['people']);

            return response()->json([
                'success' => true,
                'data' => [
                    'people' => $people,
                    'count' => count($people),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

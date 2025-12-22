<?php

namespace App\Http\Controllers;

use App\Services\OpenAIService;
use App\Services\ScraperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyAnalysisController extends Controller
{
    /**
     * Analyze a company website and create ICP
     */
    public function analyze(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'website_url' => 'required|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Scrape website
            $scraperService = new ScraperService();
            $scraperService->setApiKeyFromUser($request->user());

            $scrapeResult = $scraperService->scrapeWebsite($request->website_url);

            if (!$scrapeResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to scrape website: ' . ($scrapeResult['error'] ?? 'Unknown error'),
                ], 400);
            }

            // Analyze with AI
            $openAIService = new OpenAIService();
            $openAIService->setApiKeyFromUser($request->user());

            $icpResult = $openAIService->analyzeCompanyAndCreateICP(
                $scrapeResult['content'],
                $request->company_name,
                $request->website_url
            );

            if (!$icpResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to analyze company: ' . ($icpResult['error'] ?? 'Unknown error'),
                ], 400);
            }

            // Generate search criteria
            $criteriaResult = $openAIService->generateSearchCriteria($icpResult['icp']);

            return response()->json([
                'success' => true,
                'data' => [
                    'icp_profile' => $icpResult['icp'],
                    'search_criteria' => $criteriaResult['success'] ? $criteriaResult['criteria'] : null,
                    'scraped_content' => mb_substr($scrapeResult['content'], 0, 1000) . '...', // Preview
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

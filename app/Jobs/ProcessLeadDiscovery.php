<?php

namespace App\Jobs;

use App\Models\LeadRequest;
use App\Services\CompanySearchService;
use App\Services\OpenAIService;
use App\Services\ScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessLeadDiscovery implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public LeadRequest $leadRequest
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🚀 ProcessLeadDiscovery Started', [
            'lead_request_id' => $this->leadRequest->id,
            'company_name' => $this->leadRequest->reference_company_name,
            'target_count' => $this->leadRequest->target_count,
        ]);

        $this->leadRequest->update(['status' => 'processing']);

        try {
            // Step 1: Scrape reference company website
            $url = $this->leadRequest->reference_company_url ?? "https://{$this->leadRequest->reference_company_name}";

            $scraperService = new ScraperService();
            $scraperService->setApiKeyFromUser($this->leadRequest->user);

            $scrapeResult = $scraperService->scrapeWebsite($url);

            if (!$scrapeResult['success']) {
                // Check if it's a timeout error - try to continue with minimal content
                $error = $scrapeResult['error'] ?? 'Unknown error';
                $isTimeout = str_contains($error, 'timed out') || str_contains($error, 'timeout');
                
                Log::error('❌ Website scraping failed', [
                    'lead_request_id' => $this->leadRequest->id,
                    'error' => $error,
                    'is_timeout' => $isTimeout,
                ]);
                
                if ($isTimeout) {
                    // For timeout errors, try to continue with company name only
                    Log::warning('⚠️ Scraping timeout - continuing with company name only', [
                        'lead_request_id' => $this->leadRequest->id,
                        'company_name' => $this->leadRequest->reference_company_name,
                    ]);
                    
                    // Use minimal content for AI analysis
                    $websiteContent = "Company: {$this->leadRequest->reference_company_name}. Website: {$url}. Unable to scrape full content due to timeout.";
                } else {
                    throw new \Exception('Failed to scrape website: ' . $error);
                }
            } else {
                $websiteContent = $scrapeResult['content'];
            }

            // Step 2: Analyze with AI and create ICP
            $openAIService = new OpenAIService();
            $openAIService->setApiKeyFromUser($this->leadRequest->user);

            $icpResult = $openAIService->analyzeCompanyAndCreateICP(
                $websiteContent,
                $this->leadRequest->reference_company_name,
                $this->leadRequest->reference_company_url
            );

            if (!$icpResult['success']) {
                Log::error('❌ AI analysis failed', [
                    'lead_request_id' => $this->leadRequest->id,
                    'error' => $icpResult['error'] ?? 'Unknown error',
                ]);
                throw new \Exception('Failed to create ICP: ' . ($icpResult['error'] ?? 'Unknown error'));
            }

            $icpProfile = $icpResult['icp'];

            $criteriaResult = $openAIService->generateSearchCriteria($icpProfile, $this->leadRequest->country);

            if (!$criteriaResult['success']) {
                Log::error('❌ Search criteria generation failed', [
                    'lead_request_id' => $this->leadRequest->id,
                    'error' => $criteriaResult['error'] ?? 'Unknown error',
                ]);
                throw new \Exception('Failed to generate search criteria: ' . ($criteriaResult['error'] ?? 'Unknown error'));
            }

            $searchCriteria = $criteriaResult['criteria'];
            
            // Override country in search criteria if user provided one
            if ($this->leadRequest->country) {
                $searchCriteria['country'] = strtoupper($this->leadRequest->country);
                if (empty($searchCriteria['countries']) || !is_array($searchCriteria['countries'])) {
                    $searchCriteria['countries'] = [strtoupper($this->leadRequest->country)];
                }
            }
            // Update lead request with ICP and criteria
            $this->leadRequest->update([
                'reference_company_content' => $websiteContent,
                'icp_profile' => $icpProfile,
                'search_criteria' => $searchCriteria,
            ]);

            $companySearchService = new CompanySearchService();
            $companySearchService->setApiKeyFromUser($this->leadRequest->user);

            // Extract domain from reference company URL for lookalike search
            $referenceDomain = null;
            if ($this->leadRequest->reference_company_url) {
                $referenceDomain = parse_url($this->leadRequest->reference_company_url, PHP_URL_HOST);
            } elseif ($this->leadRequest->reference_company_name) {
                // Try to construct domain from company name
                $referenceDomain = strtolower(str_replace(' ', '', $this->leadRequest->reference_company_name)) . '.com';
            }

            $companiesResult = $companySearchService->searchCompanies(
                $searchCriteria,
                $this->leadRequest->target_count,
                $referenceDomain
            );

            if (!$companiesResult['success']) {
                $error = $companiesResult['error'] ?? 'Unknown error';
                
                Log::error('❌ Company search failed', [
                    'lead_request_id' => $this->leadRequest->id,
                    'error' => $error,
                    'apollo_free_plan_limited' => $companiesResult['apollo_free_plan_limited'] ?? false,
                ]);
                
                // If Apollo free plan is limited, provide a helpful error message
                if (!empty($companiesResult['apollo_free_plan_limited'])) {
                    throw new \Exception(
                        'Apollo free plan limitation: ' . $error . 
                        ' The system detected that Apollo is returning irrelevant results (Google, Amazon, LinkedIn) regardless of your search criteria. ' .
                        'This is a known limitation of Apollo\'s free plan. ' .
                        'To find similar companies, please upgrade to Apollo\'s paid plan, or the system will use Hunter.io for people/contact search only.'
                    );
                }
                
                throw new \Exception('Failed to search companies: ' . $error);
            }

            $companies = $companySearchService->storeCompanies($companiesResult['companies']);

            $this->leadRequest->update([
                'companies_found' => count($companies),
            ]);

            foreach ($companies as $company) {
                ProcessPersonLookup::dispatch($this->leadRequest, $company);
            }

            // Update status
            $this->leadRequest->update(['status' => 'completed']);

            Log::info('🎉 ProcessLeadDiscovery Completed Successfully', [
                'lead_request_id' => $this->leadRequest->id,
                'companies_found' => count($companies),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ProcessLeadDiscovery Failed', [
                'lead_request_id' => $this->leadRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->leadRequest->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}

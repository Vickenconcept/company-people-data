<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\LeadRequest;
use App\Models\LeadResult;
use App\Services\PeopleSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPersonLookup implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public LeadRequest $leadRequest,
        public Company $company
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('👤 ProcessPersonLookup Started', [
            'lead_request_id' => $this->leadRequest->id,
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
        ]);

        try {
            $jobTitles = $this->leadRequest->target_job_titles ?? ['CEO', 'CFO'];

            Log::info('🔍 Searching for people', [
                'lead_request_id' => $this->leadRequest->id,
                'company_id' => $this->company->id,
                'job_titles' => $jobTitles,
            ]);

            // Find people - try Apollo first, will auto-fallback to Hunter.io if needed
            $peopleSearchService = new PeopleSearchService();
            $peopleSearchService->setApiKeyFromUser($this->leadRequest->user, 'apollo');

            $peopleResult = $peopleSearchService->findPeople(
                $this->company,
                $jobTitles,
                5 // Limit per company
            );
            
            // If Apollo failed and we have a Hunter API key, try Hunter.io
            if (!$peopleResult['success'] && !empty($this->leadRequest->user->apiKeys()->where('service', 'hunter')->where('is_active', true)->first())) {
                Log::info('🔄 ProcessPersonLookup: Trying Hunter.io as fallback', [
                    'lead_request_id' => $this->leadRequest->id,
                    'company_id' => $this->company->id,
                ]);
                
                $hunterService = new PeopleSearchService();
                $hunterService->setApiKeyFromUser($this->leadRequest->user, 'hunter');
                
                $hunterResult = $hunterService->findPeople(
                    $this->company,
                    $jobTitles,
                    5
                );
                
                if ($hunterResult['success']) {
                    $peopleResult = $hunterResult;
                }
            }

            if (!$peopleResult['success']) {
                Log::warning('⚠️ ProcessPersonLookup Failed', [
                    'lead_request_id' => $this->leadRequest->id,
                    'company_id' => $this->company->id,
                    'error' => $peopleResult['error'] ?? 'Unknown error',
                ]);
                return;
            }

            $peopleFound = count($peopleResult['people']);
            Log::info('✅ People found', [
                'lead_request_id' => $this->leadRequest->id,
                'company_id' => $this->company->id,
                'people_count' => $peopleFound,
            ]);

            // Store people
            Log::info('💾 Storing people in database', [
                'lead_request_id' => $this->leadRequest->id,
                'company_id' => $this->company->id,
            ]);

            $people = $peopleSearchService->storePeople($peopleResult['people']);

            Log::info('✅ People stored', [
                'lead_request_id' => $this->leadRequest->id,
                'company_id' => $this->company->id,
                'stored_count' => count($people),
            ]);

            // Create lead results (user will manually control email sending)
            Log::info('📝 Creating lead results', [
                'lead_request_id' => $this->leadRequest->id,
                'company_id' => $this->company->id,
            ]);

            foreach ($people as $person) {
                LeadResult::create([
                    'lead_request_id' => $this->leadRequest->id,
                    'company_id' => $this->company->id,
                    'person_id' => $person->id,
                    'similarity_score' => null, // Could calculate with embeddings
                    'status' => 'pending',
                ]);

                Log::info('✅ Lead result created', [
                    'lead_request_id' => $this->leadRequest->id,
                    'company_id' => $this->company->id,
                    'person_id' => $person->id,
                    'person_name' => $person->full_name,
                    'person_title' => $person->title,
                    'has_email' => !empty($person->email),
                ]);
            }

            // Update lead request counts
            $this->leadRequest->increment('contacts_found', count($people));

            Log::info('🎉 ProcessPersonLookup Completed', [
                'lead_request_id' => $this->leadRequest->id,
                'company_id' => $this->company->id,
                'contacts_found' => count($people),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ProcessPersonLookup Exception', [
                'lead_request_id' => $this->leadRequest->id,
                'company_id' => $this->company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

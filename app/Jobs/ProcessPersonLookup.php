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
        try {
            $jobTitles = $this->leadRequest->target_job_titles ?? ['CEO', 'CFO'];

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

            $peopleWithEmail = array_values(array_filter(
                $peopleResult['people'] ?? [],
                fn (array $person): bool => !empty(trim((string) ($person['email'] ?? '')))
            ));

            if (empty($peopleWithEmail)) {
                Log::info('ℹ️ ProcessPersonLookup: No contact with email found', [
                    'lead_request_id' => $this->leadRequest->id,
                    'company_id' => $this->company->id,
                ]);
                return;
            }

            // Keep exactly one contact per company: the first matching person with an email.
            $person = $peopleSearchService->storePeople([$peopleWithEmail[0]])[0] ?? null;

            if (!$person) {
                Log::warning('⚠️ ProcessPersonLookup: Failed to store selected contact', [
                    'lead_request_id' => $this->leadRequest->id,
                    'company_id' => $this->company->id,
                ]);
                return;
            }

            $leadResult = LeadResult::updateOrCreate(
                [
                    'lead_request_id' => $this->leadRequest->id,
                    'company_id' => $this->company->id,
                ],
                [
                    'person_id' => $person->id,
                    'similarity_score' => null,
                    'status' => 'pending',
                ]
            );

            // Update lead request counts
            if ($leadResult->wasRecentlyCreated) {
                $this->leadRequest->increment('contacts_found', 1);
            }

            Log::info('🎉 ProcessPersonLookup Completed', [
                'lead_request_id' => $this->leadRequest->id,
                'company_id' => $this->company->id,
                'contacts_found' => $leadResult->wasRecentlyCreated ? 1 : 0,
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

<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\LeadRequest;
use App\Models\LeadResult;
use App\Services\PeopleSearchService;
use App\Support\JobTitleSearch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPersonLookup implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 120, 300, 600];

    public function __construct(
        public LeadRequest $leadRequest,
        public Company $company
    ) {
    }

    public function handle(): void
    {
        try {
            $jobTitles = JobTitleSearch::resolveTitles(
                $this->leadRequest->target_job_titles ?? ['CEO', 'CFO']
            );

            $peopleSearchService = new PeopleSearchService();
            $peopleSearchService->setApiKeyFromUser($this->leadRequest->user);

            $peopleResult = $peopleSearchService->findPeople(
                $this->company,
                $jobTitles,
                1
            );

            if (!$peopleResult['success'] && !empty($this->leadRequest->user->apiKeys()->where('service', 'hunter')->where('is_active', true)->first())) {
                $hunterService = new PeopleSearchService();
                $hunterService->setApiKeyFromUser($this->leadRequest->user, 'hunter');

                $hunterResult = $hunterService->findPeople(
                    $this->company,
                    $jobTitles,
                    1
                );

                if ($hunterResult['success']) {
                    $peopleResult = $hunterResult;
                }
            }

            if (!empty($peopleResult['rate_limited'])) {
                throw new \RuntimeException('Prospecting provider rate limited: '.($peopleResult['error'] ?? 'Rate limit exceeded'));
            }

            $peopleWithEmail = array_values(array_filter(
                $peopleResult['people'] ?? [],
                fn (array $person): bool => !empty(trim((string) ($person['email'] ?? '')))
            ));

            if (!empty($peopleWithEmail)) {
                $person = $peopleSearchService->storePeople([$peopleWithEmail[0]])[0] ?? null;

                if (!$person) {
                    $this->saveNoContactResult('Contact found but could not be saved to the database.');

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
                        'notes' => null,
                    ]
                );

                $this->leadRequest->update([
                    'contacts_found' => $this->leadRequest->leadResults()->whereNotNull('person_id')->count(),
                ]);

                Log::info('ProcessPersonLookup Completed', [
                    'lead_request_id' => $this->leadRequest->id,
                    'company_id' => $this->company->id,
                    'person_id' => $person->id,
                    'lead_result_id' => $leadResult->id,
                ]);

                return;
            }

            $reason = $peopleResult['error'] ?? 'No verified contact with email was found.';
            $titlesLabel = JobTitleSearch::displayLabel($this->leadRequest->target_job_titles ?? []);
            $note = JobTitleSearch::isAnybodySearch($this->leadRequest->target_job_titles ?? [])
                ? "No contact found at this company. {$reason}"
                : "No verified contact found for requested titles ({$titlesLabel}). {$reason}";

            $this->saveNoContactResult($note);

            Log::info('ProcessPersonLookup: No contact with email found', [
                'lead_request_id' => $this->leadRequest->id,
                'company_id' => $this->company->id,
                'note' => $note,
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessPersonLookup Exception', [
                'lead_request_id' => $this->leadRequest->id,
                'company_id' => $this->company->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function saveNoContactResult(string $note): void
    {
        LeadResult::updateOrCreate(
            [
                'lead_request_id' => $this->leadRequest->id,
                'company_id' => $this->company->id,
            ],
            [
                'person_id' => null,
                'similarity_score' => null,
                'status' => 'pending',
                'notes' => $note,
            ]
        );
    }
}

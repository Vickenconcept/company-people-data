<?php

namespace App\Jobs;

use App\Models\GeneratedEmail;
use App\Models\LeadResult;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class GenerateLeadEmailContent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 120;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120, 300];

    public function __construct(
        public int $leadResultId,
        public ?string $customContext = null
    ) {
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new RateLimited('openai-email-generation'),
            (new WithoutOverlapping("generate-email-{$this->leadResultId}"))->releaseAfter(30),
        ];
    }

    public function handle(): void
    {
        $leadResult = LeadResult::with(['person', 'company', 'leadRequest.user', 'generatedEmail'])
            ->find($this->leadResultId);

        if (!$leadResult || !$leadResult->person || !$leadResult->person->email) {
            return;
        }

        if ($leadResult->generatedEmail) {
            return;
        }

        try {
            $openAIService = new OpenAIService();
            $openAIService->setApiKeyFromUser($leadResult->leadRequest->user);

            $sender = $leadResult->leadRequest->user;
            $senderData = [
                'name' => $sender?->name ?? '',
                'email' => $sender?->email ?? '',
                'company_name' => config('app.name', 'Company'),
                'from_name' => config('mail.from.name'),
                'from_address' => config('mail.from.address'),
            ];

            $promptContext = $this->buildAiContextFromLeadRequest(
                $leadResult->leadRequest,
                $this->customContext
            );

            $emailResult = $openAIService->generateEmailContent(
                $leadResult->person->toArray(),
                $leadResult->company->toArray(),
                $promptContext,
                $senderData
            );

            if (!$emailResult['success']) {
                throw new \RuntimeException($emailResult['error'] ?? 'Unknown email generation error');
            }

            GeneratedEmail::updateOrCreate(
                ['lead_result_id' => $leadResult->id],
                [
                    'person_id' => $leadResult->person->id,
                    'subject' => $emailResult['subject'],
                    'body' => $emailResult['body'],
                    // Store only the user offer/context (template/manual). Campaign context is rebuilt at generation time.
                    'custom_context' => $this->customContext,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Queued email generation failed', [
                'lead_result_id' => $this->leadResultId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function buildAiContextFromLeadRequest($leadRequest, ?string $userContext): string
    {
        $offer = $this->extractUserOfferContext($userContext);

        $icp = is_array($leadRequest->icp_profile) ? $leadRequest->icp_profile : [];
        $criteria = is_array($leadRequest->search_criteria) ? $leadRequest->search_criteria : [];
        $jobTitles = is_array($leadRequest->target_job_titles) ? $leadRequest->target_job_titles : [];

        $parts = [];
        $parts[] = "Campaign context:";
        $parts[] = "- Reference company: " . ($leadRequest->reference_company_name ?: 'N/A');

        if (!empty($leadRequest->reference_company_url)) {
            $parts[] = "- Reference URL: " . $leadRequest->reference_company_url;
        }

        if (!empty($leadRequest->country)) {
            $parts[] = "- Target country: " . $leadRequest->country;
        }

        if (!empty($jobTitles)) {
            $parts[] = "- Target roles: " . implode(', ', array_slice($jobTitles, 0, 10));
        }

        $industry = data_get($icp, 'industry');
        $valueProp = data_get($icp, 'value_proposition');
        if (!empty($industry)) {
            $parts[] = "- ICP industry: " . $industry;
        }
        if (!empty($valueProp)) {
            $parts[] = "- ICP value proposition: " . $valueProp;
        }

        $keywords = data_get($criteria, 'keywords');
        if (is_array($keywords) && !empty($keywords)) {
            $parts[] = "- Search keywords: " . implode(', ', array_slice($keywords, 0, 12));
        }

        if (!empty($leadRequest->reference_company_content)) {
            $content = trim((string) $leadRequest->reference_company_content);
            if ($content !== '') {
                $parts[] = "\nReference company notes (scraped):\n" . mb_substr($content, 0, 800);
            }
        }

        if ($offer !== '') {
            $parts[] = "\nOffer / what we want to pitch:\n" . $offer;
        }

        return mb_substr(implode("\n", $parts), 0, 2000);
    }

    private function extractUserOfferContext(?string $storedContext): string
    {
        $s = trim((string) ($storedContext ?? ''));
        if ($s === '') {
            return '';
        }

        $marker = 'Offer / what we want to pitch:';
        if (str_contains($s, $marker)) {
            return trim(str_replace($marker, '', $s));
        }

        return $s;
    }
}

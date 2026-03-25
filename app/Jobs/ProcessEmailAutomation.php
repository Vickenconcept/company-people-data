<?php

namespace App\Jobs;

use App\Models\LeadResult;
use App\Models\QueuedEmail;
use App\Services\EmailAutomationService;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ProcessEmailAutomation implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 120;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public LeadResult $leadResult
    ) {
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new RateLimited('email-send'),
            (new WithoutOverlapping("send-email-{$this->leadResult->id}"))->releaseAfter(30),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Refresh to load relationships
        $this->leadResult->load(['person', 'company', 'leadRequest.user']);

        Log::info('📧 ProcessEmailAutomation Started', [
            'lead_result_id' => $this->leadResult->id,
            'person_id' => $this->leadResult->person_id,
            'company_id' => $this->leadResult->company_id,
        ]);

        try {
            if (!$this->leadResult->person || !$this->leadResult->person->email) {
                Log::warning('⚠️ ProcessEmailAutomation: No email found', [
                    'lead_result_id' => $this->leadResult->id,
                ]);
                return;
            }

            $emailAutomationService = new EmailAutomationService();

            // Check if a queued email already exists (user-provided content)
            $queuedEmail = QueuedEmail::where('lead_result_id', $this->leadResult->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$queuedEmail) {
                // No existing queued email, generate one with AI
                Log::info('✉️ Generating email content with AI', [
                    'lead_result_id' => $this->leadResult->id,
                    'person_email' => $this->leadResult->person->email,
                    'person_name' => $this->leadResult->person->full_name,
                ]);

                $openAIService = new OpenAIService();
                $openAIService->setApiKeyFromUser($this->leadResult->leadRequest->user);

                $sender = $this->leadResult->leadRequest->user;
                $senderData = [
                    'name' => $sender?->name ?? '',
                    'email' => $sender?->email ?? '',
                    'company_name' => config('app.name', 'Company'),
                    'from_name' => config('mail.from.name'),
                    'from_address' => config('mail.from.address'),
                ];

                $emailResult = $openAIService->generateEmailContent(
                    $this->leadResult->person->toArray(),
                    $this->leadResult->company->toArray(),
                    null,
                    $senderData
                );

                if (!$emailResult['success']) {
                    Log::error('❌ Email generation failed', [
                        'lead_result_id' => $this->leadResult->id,
                        'error' => $emailResult['error'] ?? 'Unknown error',
                    ]);
                    throw new \Exception('Failed to generate email: ' . ($emailResult['error'] ?? 'Unknown error'));
                }

                Log::info('✅ Email content generated', [
                    'lead_result_id' => $this->leadResult->id,
                    'subject' => $emailResult['subject'],
                ]);

                // Create queued email
                Log::info('📬 Creating queued email record', [
                    'lead_result_id' => $this->leadResult->id,
                ]);

                $queuedEmail = $emailAutomationService->queueEmail(
                    $this->leadResult->person,
                    $emailResult['subject'],
                    $emailResult['body'],
                    $this->leadResult->id
                );

                Log::info('✅ Queued email created', [
                    'lead_result_id' => $this->leadResult->id,
                    'queued_email_id' => $queuedEmail->id,
                ]);
            } else {
                Log::info('📬 Using existing queued email', [
                    'lead_result_id' => $this->leadResult->id,
                    'queued_email_id' => $queuedEmail->id,
                ]);
            }

            // Send email
            Log::info('📤 Sending email', [
                'lead_result_id' => $this->leadResult->id,
                'to' => $queuedEmail->to_email,
            ]);

            $sent = $emailAutomationService->sendEmail($queuedEmail);

            if ($sent) {
                $this->leadResult->update(['status' => 'contacted']);
                Log::info('✅ Email sent successfully', [
                    'lead_result_id' => $this->leadResult->id,
                    'queued_email_id' => $queuedEmail->id,
                ]);
            } else {
                Log::warning('⚠️ Email sending failed', [
                    'lead_result_id' => $this->leadResult->id,
                    'queued_email_id' => $queuedEmail->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('❌ ProcessEmailAutomation Exception', [
                'lead_result_id' => $this->leadResult->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

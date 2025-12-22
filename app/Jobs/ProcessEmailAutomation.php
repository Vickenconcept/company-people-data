<?php

namespace App\Jobs;

use App\Models\LeadResult;
use App\Models\QueuedEmail;
use App\Services\EmailAutomationService;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessEmailAutomation implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public LeadResult $leadResult
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
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

            Log::info('✉️ Generating email content with AI', [
                'lead_result_id' => $this->leadResult->id,
                'person_email' => $this->leadResult->person->email,
                'person_name' => $this->leadResult->person->full_name,
            ]);

            // Generate email content with AI
            $openAIService = new OpenAIService();
            $openAIService->setApiKeyFromUser($this->leadResult->leadRequest->user);

            $emailResult = $openAIService->generateEmailContent(
                $this->leadResult->person->toArray(),
                $this->leadResult->company->toArray()
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

            $emailAutomationService = new EmailAutomationService();

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

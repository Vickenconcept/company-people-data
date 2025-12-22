<?php

namespace App\Services;

use App\Models\Person;
use App\Models\QueuedEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailAutomationService
{
    /**
     * Send email using Laravel Mail (SMTP)
     */
    public function sendEmail(QueuedEmail $queuedEmail): bool
    {
        try {
            Mail::raw($queuedEmail->body, function ($message) use ($queuedEmail) {
                $message->to($queuedEmail->to_email)
                    ->subject($queuedEmail->subject)
                    ->from(
                        config('mail.from.address'),
                        config('mail.from.name')
                    );
            });

            $queuedEmail->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Email Send Failed', [
                'queued_email_id' => $queuedEmail->id,
                'error' => $e->getMessage(),
            ]);

            $queuedEmail->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create queued email record
     */
    public function queueEmail(Person $person, string $subject, string $body, int $leadResultId): QueuedEmail
    {
        return QueuedEmail::create([
            'lead_result_id' => $leadResultId,
            'person_id' => $person->id,
            'to_email' => $person->email,
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
        ]);
    }
}

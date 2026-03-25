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
            $bodyHtml = $this->toHtmlBody($queuedEmail->body);

            Mail::html($bodyHtml, function ($message) use ($queuedEmail, $bodyHtml) {
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

    private function toHtmlBody(string $body): string
    {
        $trimmed = trim($body);

        if ($trimmed === '') {
            return '<p></p>';
        }

        // If it's already HTML, keep it as-is.
        if (str_contains($trimmed, '<')) {
            return $trimmed;
        }

        // Convert plain text newlines into HTML paragraphs/br tags.
        $escaped = htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8');
        $paragraphs = preg_split("/\r?\n\r?\n/", $escaped) ?: [$escaped];

        $htmlParagraphs = array_map(function (string $p) {
            $p = trim($p);
            return $p === '' ? '<p></p>' : '<p>' . nl2br($p, false) . '</p>';
        }, $paragraphs);

        return implode('', $htmlParagraphs);
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

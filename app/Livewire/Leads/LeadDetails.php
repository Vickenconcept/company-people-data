<?php

namespace App\Livewire\Leads;

use App\Models\GeneratedEmail;
use App\Models\LeadRequest;
use App\Models\Tag;
use App\Models\EmailTemplate;
use App\Services\EmailAutomationService;
use App\Services\OpenAIService;
use App\Jobs\ProcessEmailAutomation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Lead Details'])]
class LeadDetails extends Component
{
    use WithPagination;

    public LeadRequest $leadRequest;
    public string $activeTab = 'contacts'; // 'contacts' or 'companies'
    public bool $showICP = false; // Toggle for showing full ICP details

    // Email management properties
    public array $selectedLeadResults = [];
    public bool $showGenerateModal = false;
    public bool $showQueueModal = false;
    public bool $showMassSendModal = false;
    public bool $showQueuedEmailsModal = false;
    public bool $showEmailModal = false;
    public string $customContext = '';
    public array $generatingLeadResultIds = []; // Track which rows are currently generating
    public ?int $viewingLeadResultId = null; // Which email is being viewed in modal
    public ?GeneratedEmail $viewingEmail = null;
    public bool $isRegenerating = false;
    public bool $isQueueing = false;
    public bool $isMassQueueing = false;
    public ?string $message = null;
    public string $messageType = 'success'; // 'success' or 'error'
    public int $batchSize = 5; // Generate 5 emails at a time
    public bool $showBulkStatusModal = false;
    public ?string $bulkStatus = null;
    public int $massSendCount = 0;
    
    // Advanced filtering
    public ?string $filterStatus = null;
    public ?string $filterIndustry = null;
    public ?string $filterJobTitle = null;
    public ?bool $filterHasEmail = null;
    public ?bool $filterHasGeneratedEmail = null;
    public ?int $filterTagId = null;
    
    // Notes
    public bool $showNotesModal = false;
    public ?int $editingNotesLeadResultId = null;
    public string $notesContent = '';
    
    // Tags
    public bool $showTagsModal = false;
    public ?int $taggingLeadResultId = null;
    public array $selectedTagIds = [];
    public string $newTagName = '';
    public string $newTagColor = '#3B82F6';
    
    // Email Templates
    public ?int $selectedTemplateId = null;

    public function mount(int $id)
    {
        $this->leadRequest = LeadRequest::where('user_id', Auth::id())
            ->with(['leadResults.company', 'leadResults.person'])
            ->findOrFail($id);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage(); // Reset pagination when switching tabs
        $this->selectedLeadResults = []; // Clear selections when switching tabs
    }

    public function toggleSelect(int $leadResultId): void
    {
        if (in_array($leadResultId, $this->selectedLeadResults)) {
            $this->selectedLeadResults = array_values(array_filter($this->selectedLeadResults, fn($id) => $id !== $leadResultId));
        } else {
            $this->selectedLeadResults[] = $leadResultId;
        }
    }

    public function selectAll(): void
    {
        $results = $this->leadRequest->leadResults()
            ->with(['person'])
            ->get()
            ->filter(fn($result) => $result->person && $result->person->email)
            ->pluck('id')
            ->toArray();
        
        $this->selectedLeadResults = $results;
    }

    public function deselectAll(): void
    {
        $this->selectedLeadResults = [];
    }

    public function toggleSelectAll(bool $checked): void
    {
        if ($checked) {
            $this->selectAll();
            return;
        }

        $this->deselectAll();
    }

    public function openGenerateModal(): void
    {
        if (empty($this->selectedLeadResults)) {
            $this->message = 'Please select at least one contact with an email address.';
            $this->messageType = 'error';
            return;
        }

        $this->showGenerateModal = true;
        $this->message = null;
        $this->customContext = '';
        $this->selectedTemplateId = null;
    }

    public function closeGenerateModal(): void
    {
        $this->showGenerateModal = false;
        $this->customContext = '';
        $this->selectedTemplateId = null;
    }

    public function startGenerating(): void
    {
        if (empty($this->selectedLeadResults)) {
            $this->message = 'Please select at least one contact.';
            $this->messageType = 'error';
            return;
        }

        $this->closeGenerateModal();
        $this->message = null;

        $queuedCount = $this->generateBatch();

        if ($queuedCount > 0) {
            $this->message = "{$queuedCount} email generation job(s) queued. Drafting will run in background with rate limiting.";
            $this->messageType = 'success';
        } else {
            $this->message = 'All selected contacts already have generated emails, or no valid emails were found.';
            $this->messageType = 'error';
        }
    }

    public function generateBatch(): int
    {
        $leadResults = $this->leadRequest->leadResults()
            ->whereIn('id', $this->selectedLeadResults)
            ->with(['person', 'company', 'leadRequest.user', 'generatedEmail'])
            ->get()
            ->filter(function ($result) {
                return $result->person 
                    && $result->person->email 
                    && !$result->generatedEmail;
            });

        if ($leadResults->isEmpty()) {
            return 0;
        }

        // For queued jobs we pass only the user offer/context; the job will rebuild prompt context.
        return $this->queueEmailGenerationJobs($leadResults->values(), $this->customContext ?: null);
    }

    public function viewEmail(int $leadResultId): void
    {
        $this->viewingLeadResultId = $leadResultId;
        $this->viewingEmail = GeneratedEmail::where('lead_result_id', $leadResultId)
            ->with(['leadResult.person', 'leadResult.company'])
            ->first();
        
        if (!$this->viewingEmail) {
            $this->message = 'No generated email found for this contact.';
            $this->messageType = 'error';
            return;
        }
        
        // For the individual email modal, the template/context inputs should reflect
        // the user offer context used for this generated email.
        $this->selectedTemplateId = null;
        $this->customContext = $this->extractUserOfferContext($this->viewingEmail->custom_context ?? null);

        $this->showEmailModal = true;
    }

    public function closeEmailModal(): void
    {
        $this->showEmailModal = false;
        $this->viewingLeadResultId = null;
        $this->viewingEmail = null;
    }

    public function generateSingleEmail(int $leadResultId): void
    {
        // Add to generating list
        if (!in_array($leadResultId, $this->generatingLeadResultIds)) {
            $this->generatingLeadResultIds[] = $leadResultId;
        }

        try {
            $leadResult = $this->leadRequest->leadResults()
                ->with(['person', 'company', 'leadRequest.user', 'generatedEmail'])
                ->find($leadResultId);

            if (!$leadResult || !$leadResult->person || !$leadResult->person->email) {
                $this->message = 'Contact not found or has no email address.';
                $this->messageType = 'error';
                $this->generatingLeadResultIds = array_values(array_filter(
                    $this->generatingLeadResultIds,
                    fn($id) => $id !== $leadResultId
                ));
                return;
            }

            // Check if already generated
            if ($leadResult->generatedEmail) {
                $this->message = 'Email already generated for this contact.';
                $this->messageType = 'info';
                $this->generatingLeadResultIds = array_values(array_filter(
                    $this->generatingLeadResultIds,
                    fn($id) => $id !== $leadResultId
                ));
                return;
            }

            $openAIService = new OpenAIService();
            $openAIService->setApiKeyFromUser($leadResult->leadRequest->user);

            $sender = $leadResult->leadRequest->user;
            $senderData = [
                'name' => $sender?->name ?? '',
                'email' => $sender?->email ?? '',
                'company_name' => '',
                'from_name' => config('mail.from.name'),
                'from_address' => config('mail.from.address'),
            ];

            $emailResult = $openAIService->generateEmailContent(
                $leadResult->person->toArray(),
                $leadResult->company->toArray(),
                $this->buildAiContext($this->customContext ?: null),
                $senderData
            );

            if ($emailResult['success']) {
                // Save to database
                GeneratedEmail::create([
                    'lead_result_id' => $leadResult->id,
                    'person_id' => $leadResult->person->id,
                    'subject' => $emailResult['subject'],
                    'body' => $emailResult['body'],
                    // Store only the user offer/context (template/manual). Campaign context is rebuilt at generation time.
                    'custom_context' => $this->customContext,
                ]);

                // Refresh to load the new generated email
                $this->leadRequest->refresh();
                
                $this->message = 'Email generated successfully!';
                $this->messageType = 'success';
            } else {
                $this->message = 'Failed to generate email. Please try again.';
                $this->messageType = 'error';
            }
        } catch (\Exception $e) {
            Log::error('Single email generation failed', [
                'lead_result_id' => $leadResultId,
                'error' => $e->getMessage(),
            ]);
            $this->message = 'An error occurred: ' . $e->getMessage();
            $this->messageType = 'error';
        } finally {
            // Remove from generating list
            $this->generatingLeadResultIds = array_values(array_filter(
                $this->generatingLeadResultIds,
                fn($id) => $id !== $leadResultId
            ));
        }
    }

    public function regenerateEmail(): void
    {
        if (!$this->viewingEmail || !$this->viewingLeadResultId) {
            return;
        }

        $this->isRegenerating = true;
        
        try {
            $leadResult = $this->leadRequest->leadResults()
                ->with(['person', 'company', 'leadRequest.user'])
                ->find($this->viewingLeadResultId);

            if (!$leadResult || !$leadResult->person) {
                $this->message = 'Contact not found.';
                $this->messageType = 'error';
                return;
            }

            $openAIService = new OpenAIService();
            $openAIService->setApiKeyFromUser($leadResult->leadRequest->user);

            $sender = $leadResult->leadRequest->user;
            $senderData = [
                'name' => $sender?->name ?? '',
                'email' => $sender?->email ?? '',
                'company_name' => '',
                'from_name' => config('mail.from.name'),
                'from_address' => config('mail.from.address'),
            ];

            $emailResult = $openAIService->generateEmailContent(
                $leadResult->person->toArray(),
                $leadResult->company->toArray(),
                $this->buildAiContext($this->customContext !== '' ? $this->customContext : ($this->viewingEmail->custom_context ?? null)),
                $senderData
            );

            if ($emailResult['success']) {
                $userOffer = $this->customContext !== '' ? $this->customContext : ($this->viewingEmail->custom_context ?? null);
                $this->viewingEmail->update([
                    'subject' => $emailResult['subject'],
                    'body' => $emailResult['body'],
                    'custom_context' => $userOffer,
                ]);
                
                $this->viewingEmail->refresh();
                $this->message = 'Email regenerated successfully!';
                $this->messageType = 'success';
            } else {
                $this->message = 'Failed to regenerate email. Please try again.';
                $this->messageType = 'error';
            }
        } catch (\Exception $e) {
            Log::error('Email regeneration failed', ['error' => $e->getMessage()]);
            $this->message = 'An error occurred: ' . $e->getMessage();
            $this->messageType = 'error';
        } finally {
            $this->isRegenerating = false;
        }
    }

    public function updateEmailSubject(string $value): void
    {
        if ($this->viewingEmail) {
            $this->viewingEmail->update(['subject' => $value]);
            $this->viewingEmail->refresh();
        }
    }

    public function updateEmailBody(string $value): void
    {
        if ($this->viewingEmail) {
            $this->viewingEmail->update(['body' => $value]);
            $this->viewingEmail->refresh();
        }
    }

    public function queueSelectedEmails(): void
    {
        // Get all selected lead results with generated emails
        $leadResults = $this->leadRequest->leadResults()
            ->whereIn('id', $this->selectedLeadResults)
            ->with(['person', 'company', 'leadRequest.user', 'generatedEmail'])
            ->get()
            ->filter(fn($result) => $result->generatedEmail);

        if ($leadResults->isEmpty()) {
            $this->message = 'No generated emails found for selected contacts.';
            $this->messageType = 'error';
            return;
        }

        $this->isQueueing = true;
        $this->message = null;

        try {
            $queuedCount = $this->queueAndDispatchLeadResults($leadResults->values());

            if ($queuedCount > 0) {
                $this->message = "{$queuedCount} email(s) queued successfully! They will be sent in the background.";
                $this->messageType = 'success';
                $this->selectedLeadResults = [];
                $this->leadRequest->refresh();
            } else {
                $this->message = 'Failed to queue emails. Please try again.';
                $this->messageType = 'error';
            }
        } catch (\Exception $e) {
            Log::error('Email queueing failed', ['error' => $e->getMessage()]);
            $this->message = 'An error occurred while queueing emails: ' . $e->getMessage();
            $this->messageType = 'error';
        } finally {
            $this->isQueueing = false;
        }
    }

    public function sendGeneratedEmail(int $leadResultId): void
    {
        $this->message = null;

        try {
            $leadResult = $this->leadRequest->leadResults()
                ->where('id', $leadResultId)
                ->with(['person', 'generatedEmail'])
                ->first();

            if (!$leadResult || !$leadResult->person || !$leadResult->person->email) {
                $this->message = 'Contact not found or has no email address.';
                $this->messageType = 'error';
                return;
            }

            if (!$leadResult->generatedEmail) {
                $this->message = 'No generated email found for this contact.';
                $this->messageType = 'error';
                return;
            }

            $queuedCount = $this->queueAndDispatchLeadResults(collect([$leadResult]));

            if ($queuedCount > 0) {
                $this->message = 'Email queued and will be sent shortly.';
                $this->messageType = 'success';
                $this->leadRequest->refresh();
            } else {
                $this->message = 'This email is already queued or already sent.';
                $this->messageType = 'error';
            }
        } catch (\Exception $e) {
            Log::error('Single generated email send failed', [
                'lead_result_id' => $leadResultId,
                'error' => $e->getMessage(),
            ]);
            $this->message = 'An error occurred while sending this email: ' . $e->getMessage();
            $this->messageType = 'error';
        }
    }

    public function openQueueModal(): void
    {
        // Check if there are generated emails for selected contacts
        $hasGeneratedEmails = $this->leadRequest->leadResults()
            ->whereIn('id', $this->selectedLeadResults)
            ->whereHas('generatedEmail')
            ->exists();

        if (!$hasGeneratedEmails) {
            $this->message = 'No generated emails found for selected contacts.';
            $this->messageType = 'error';
            return;
        }

        $this->showQueueModal = true;
        $this->message = null;
    }

    public function closeQueueModal(): void
    {
        $this->showQueueModal = false;
    }

    public function openMassSendModal(): void
    {
        $this->massSendCount = $this->leadRequest->leadResults()
            ->whereHas('generatedEmail')
            ->whereHas('person', function ($query) {
                $query->whereNotNull('email')->where('email', '!=', '');
            })
            ->whereDoesntHave('queuedEmails', function ($query) {
                $query->whereIn('status', ['pending', 'sent']);
            })
            ->count();

        if ($this->massSendCount === 0) {
            $this->message = 'No generated unsent emails found for this campaign.';
            $this->messageType = 'error';
            return;
        }

        $this->showMassSendModal = true;
        $this->message = null;
    }

    public function closeMassSendModal(): void
    {
        $this->showMassSendModal = false;
    }

    public function massSendGeneratedEmails(): void
    {
        $this->isMassQueueing = true;
        $this->message = null;

        try {
            $leadResults = $this->leadRequest->leadResults()
                ->whereHas('generatedEmail')
                ->whereHas('person', function ($query) {
                    $query->whereNotNull('email')->where('email', '!=', '');
                })
                ->whereDoesntHave('queuedEmails', function ($query) {
                    $query->whereIn('status', ['pending', 'sent']);
                })
                ->with(['person', 'generatedEmail'])
                ->get();

            if ($leadResults->isEmpty()) {
                $this->message = 'No generated unsent emails found for this campaign.';
                $this->messageType = 'error';
                return;
            }

            $queuedCount = $this->queueAndDispatchLeadResults($leadResults->values());

            if ($queuedCount > 0) {
                $this->message = "{$queuedCount} email(s) queued for mass sending. Delivery is throttled automatically to avoid rate limits.";
                $this->messageType = 'success';
                $this->closeMassSendModal();
                $this->leadRequest->refresh();
            } else {
                $this->message = 'No emails were queued. They may already be queued or sent.';
                $this->messageType = 'error';
            }
        } catch (\Exception $e) {
            Log::error('Mass email queueing failed', ['error' => $e->getMessage()]);
            $this->message = 'An error occurred while mass queueing emails: ' . $e->getMessage();
            $this->messageType = 'error';
        } finally {
            $this->isMassQueueing = false;
        }
    }

    private function queueAndDispatchLeadResults($leadResults): int
    {
        $emailAutomationService = new EmailAutomationService();
        $ratePerMinute = max(1, min(120, (int) env('MASS_EMAILS_RATE_PER_MINUTE', 30)));
        $queuedCount = 0;
        $baseTime = Carbon::now();

        foreach ($leadResults as $leadResult) {
            if (!$leadResult->person || !$leadResult->generatedEmail) {
                continue;
            }

            $hasQueuedOrSent = $leadResult->queuedEmails()
                ->whereIn('status', ['pending', 'sent'])
                ->exists();

            if ($hasQueuedOrSent) {
                continue;
            }

            $emailAutomationService->queueEmail(
                $leadResult->person,
                $leadResult->generatedEmail->subject,
                $leadResult->generatedEmail->body,
                $leadResult->id
            );

            $delayMs = (int) round(($queuedCount * 60000) / $ratePerMinute);
            ProcessEmailAutomation::dispatch($leadResult)->delay($baseTime->copy()->addMilliseconds($delayMs));
            $queuedCount++;
        }

        return $queuedCount;
    }

    private function queueEmailGenerationJobs(Collection $leadResults, ?string $customContext = null): int
    {
        $ratePerMinute = max(1, min(120, (int) env('MASS_EMAIL_GENERATION_RATE_PER_MINUTE', 20)));
        $queuedCount = 0;
        $baseTime = Carbon::now();

        foreach ($leadResults as $leadResult) {
            if (!$leadResult->person || !$leadResult->person->email || $leadResult->generatedEmail) {
                continue;
            }

            $delayMs = (int) round(($queuedCount * 60000) / $ratePerMinute);
            \App\Jobs\GenerateLeadEmailContent::dispatch($leadResult->id, $customContext)
                ->delay($baseTime->copy()->addMilliseconds($delayMs));
            $queuedCount++;
        }

        return $queuedCount;
    }

    private function buildAiContext(?string $userContext): string
    {
        $lr = $this->leadRequest;

        $offer = $this->extractUserOfferContext($userContext);

        $icp = is_array($lr->icp_profile) ? $lr->icp_profile : [];
        $criteria = is_array($lr->search_criteria) ? $lr->search_criteria : [];
        $jobTitles = is_array($lr->target_job_titles) ? $lr->target_job_titles : [];

        $parts = [];

        $parts[] = "Primary instruction from user/template:";
        $parts[] = $offer !== '' ? $offer : 'No explicit instruction provided.';
        $parts[] = "";
        $parts[] = "Campaign background (supporting context only):";
        $parts[] = "- Reference company: " . ($lr->reference_company_name ?: 'N/A');
        if (!empty($lr->reference_company_url)) {
            $parts[] = "- Reference URL: " . $lr->reference_company_url;
        }
        if (!empty($lr->country)) {
            $parts[] = "- Target country: " . $lr->country;
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

        if (!empty($lr->reference_company_content)) {
            $content = trim((string) $lr->reference_company_content);
            if ($content !== '') {
                $parts[] = "\nReference company notes (scraped):\n" . mb_substr($content, 0, 800);
            }
        }

        $context = implode("\n", $parts);

        // Keep it reasonably sized for prompt safety.
        return mb_substr($context, 0, 2000);
    }

    private function extractUserOfferContext(?string $storedContext): string
    {
        $s = trim((string) ($storedContext ?? ''));
        if ($s === '') {
            return '';
        }

        // For older rows we may have stored a full prompt that includes campaign context.
        // Extract only the user offer portion so the modal input looks right.
        $marker = 'Offer / what we want to pitch:';
        if (str_contains($s, $marker)) {
            $after = trim(str_replace($marker, '', $s));
            return $after;
        }

        return $s;
    }

    public function openQueuedEmailsModal(): void
    {
        $this->showQueuedEmailsModal = true;
    }

    public function closeQueuedEmailsModal(): void
    {
        $this->showQueuedEmailsModal = false;
    }

    public function dismissMessage(): void
    {
        $this->message = null;
    }

    public function updateLeadStatus(int $leadResultId, string $status): void
    {
        $validStatuses = ['pending', 'contacted', 'responded', 'converted', 'rejected'];
        
        if (!in_array($status, $validStatuses)) {
            $this->message = 'Invalid status.';
            $this->messageType = 'error';
            return;
        }

        $leadResult = $this->leadRequest->leadResults()
            ->where('id', $leadResultId)
            ->first();

        if (!$leadResult) {
            $this->message = 'Lead result not found.';
            $this->messageType = 'error';
            return;
        }

        $leadResult->update(['status' => $status]);
        
        $this->message = 'Status updated successfully!';
        $this->messageType = 'success';
        $this->leadRequest->refresh();
    }

    public function openBulkStatusModal(): void
    {
        if (empty($this->selectedLeadResults)) {
            $this->message = 'Please select at least one contact.';
            $this->messageType = 'error';
            return;
        }

        $this->showBulkStatusModal = true;
        $this->bulkStatus = null;
    }

    public function closeBulkStatusModal(): void
    {
        $this->showBulkStatusModal = false;
        $this->bulkStatus = null;
    }

    public function updateBulkStatus(): void
    {
        if (empty($this->selectedLeadResults) || empty($this->bulkStatus)) {
            $this->message = 'Please select contacts and a status.';
            $this->messageType = 'error';
            return;
        }

        $validStatuses = ['pending', 'contacted', 'responded', 'converted', 'rejected'];
        
        if (!in_array($this->bulkStatus, $validStatuses)) {
            $this->message = 'Invalid status.';
            $this->messageType = 'error';
            return;
        }

        $updated = $this->leadRequest->leadResults()
            ->whereIn('id', $this->selectedLeadResults)
            ->update(['status' => $this->bulkStatus]);

        $this->message = "{$updated} contact(s) status updated to " . ucfirst($this->bulkStatus) . "!";
        $this->messageType = 'success';
        $this->selectedLeadResults = [];
        $this->closeBulkStatusModal();
        $this->leadRequest->refresh();
    }

    // Notes functionality
    public function openNotesModal(int $leadResultId): void
    {
        $leadResult = $this->leadRequest->leadResults()->find($leadResultId);
        if (!$leadResult) {
            return;
        }
        
        $this->editingNotesLeadResultId = $leadResultId;
        $this->notesContent = $leadResult->notes ?? '';
        $this->showNotesModal = true;
    }

    public function closeNotesModal(): void
    {
        $this->showNotesModal = false;
        $this->editingNotesLeadResultId = null;
        $this->notesContent = '';
    }

    public function saveNotes(): void
    {
        if (!$this->editingNotesLeadResultId) {
            return;
        }

        $leadResult = $this->leadRequest->leadResults()->find($this->editingNotesLeadResultId);
        if (!$leadResult) {
            return;
        }

        $leadResult->update(['notes' => $this->notesContent]);
        $this->message = 'Notes saved successfully!';
        $this->messageType = 'success';
        $this->closeNotesModal();
        $this->leadRequest->refresh();
    }

    // Tags functionality
    public function openTagsModal(int $leadResultId): void
    {
        $leadResult = $this->leadRequest->leadResults()->with('tags')->find($leadResultId);
        if (!$leadResult) {
            return;
        }
        
        $this->taggingLeadResultId = $leadResultId;
        $this->selectedTagIds = $leadResult->tags->pluck('id')->toArray();
        $this->showTagsModal = true;
    }

    public function closeTagsModal(): void
    {
        $this->showTagsModal = false;
        $this->taggingLeadResultId = null;
        $this->selectedTagIds = [];
        $this->newTagName = '';
        $this->newTagColor = '#3B82F6';
    }

    public function createTag(): void
    {
        if (empty($this->newTagName)) {
            $this->message = 'Tag name is required.';
            $this->messageType = 'error';
            return;
        }

        $tag = Tag::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'name' => trim($this->newTagName),
            ],
            [
                'color' => $this->newTagColor,
            ]
        );

        if (!in_array($tag->id, $this->selectedTagIds)) {
            $this->selectedTagIds[] = $tag->id;
        }

        $this->newTagName = '';
        $this->newTagColor = '#3B82F6';
        $this->message = 'Tag created successfully!';
        $this->messageType = 'success';
    }

    public function saveTags(): void
    {
        if (!$this->taggingLeadResultId) {
            return;
        }

        $leadResult = $this->leadRequest->leadResults()->find($this->taggingLeadResultId);
        if (!$leadResult) {
            return;
        }

        // Only sync tags that belong to the current user
        $userTagIds = Tag::where('user_id', Auth::id())
            ->whereIn('id', $this->selectedTagIds)
            ->pluck('id')
            ->toArray();

        $leadResult->tags()->sync($userTagIds);
        
        $this->message = 'Tags updated successfully!';
        $this->messageType = 'success';
        $this->closeTagsModal();
        $this->leadRequest->refresh();
    }

    public function deleteTag(int $tagId): void
    {
        $tag = Tag::where('user_id', Auth::id())->find($tagId);
        if (!$tag) {
            return;
        }

        $tag->delete();
        $this->selectedTagIds = array_values(array_filter($this->selectedTagIds, fn($id) => $id !== $tagId));
        $this->message = 'Tag deleted successfully!';
        $this->messageType = 'success';
    }

    // Filtering methods
    public function clearFilters(): void
    {
        $this->filterStatus = null;
        $this->filterIndustry = null;
        $this->filterJobTitle = null;
        $this->filterHasEmail = null;
        $this->filterHasGeneratedEmail = null;
        $this->filterTagId = null;
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterIndustry()
    {
        $this->resetPage();
    }

    public function updatingFilterJobTitle()
    {
        $this->resetPage();
    }

    public function updatingFilterHasEmail()
    {
        $this->resetPage();
    }

    public function updatingFilterHasGeneratedEmail()
    {
        $this->resetPage();
    }

    public function updatingFilterTagId()
    {
        $this->resetPage();
    }

    // Email Template methods
    public function applyTemplate($templateId): void
    {
        if (empty($templateId)) {
            $this->selectedTemplateId = null;
            $this->customContext = '';
            return;
        }

        $template = EmailTemplate::where('user_id', Auth::id())->find($templateId);
        if (!$template) {
            return;
        }

        $this->selectedTemplateId = (int)$templateId;
        $this->customContext = $template->custom_context ?? '';
    }

    public function updatedSelectedTemplateId($value): void
    {
        if ($value) {
            $this->applyTemplate($value);
        }
    }

    public function render()
    {
        $query = $this->leadRequest->leadResults()
            ->with(['company', 'person', 'queuedEmails', 'generatedEmail', 'tags']);

        // Apply filters
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterIndustry) {
            $query->whereHas('company', function ($q) {
                $q->where('industry', 'like', '%' . $this->filterIndustry . '%');
            });
        }

        if ($this->filterJobTitle) {
            $query->whereHas('person', function ($q) {
                $q->where('title', 'like', '%' . $this->filterJobTitle . '%');
            });
        }

        if ($this->filterHasEmail !== null) {
            if ($this->filterHasEmail) {
                $query->whereHas('person', function ($q) {
                    $q->whereNotNull('email')->where('email', '!=', '');
                });
            } else {
                $query->whereHas('person', function ($q) {
                    $q->whereNull('email')->orWhere('email', '');
                });
            }
        }

        if ($this->filterHasGeneratedEmail !== null) {
            if ($this->filterHasGeneratedEmail) {
                $query->whereHas('generatedEmail');
            } else {
                $query->whereDoesntHave('generatedEmail');
            }
        }

        if ($this->filterTagId) {
            $query->whereHas('tags', function ($q) {
                $q->where('tags.id', $this->filterTagId);
            });
        }

        $results = $query->latest()->paginate(20);

        // Get unique industries for filter dropdown
        $industries = $this->leadRequest->leadResults()
            ->with('company')
            ->get()
            ->pluck('company.industry')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Get unique job titles for filter dropdown
        $jobTitles = $this->leadRequest->leadResults()
            ->with('person')
            ->get()
            ->pluck('person.title')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Get user's tags
        $tags = Tag::where('user_id', Auth::id())->orderBy('name')->get();

        // Get user's email templates
        $emailTemplates = EmailTemplate::where('user_id', Auth::id())->orderBy('name')->get();

        // Get unique companies found for this lead request
        $companies = $this->leadRequest->leadResults()
            ->with('company')
            ->get()
            ->pluck('company')
            ->unique('id')
            ->values();

        // Get queued emails for this lead request
        $queuedEmails = $this->leadRequest->leadResults()
            ->with(['queuedEmails' => function ($query) {
                $query->latest();
            }, 'company', 'person'])
            ->get()
            ->pluck('queuedEmails')
            ->flatten()
            ->sortByDesc('created_at')
            ->values();

        return view('livewire.leads.lead-details', [
            'results' => $results,
            'companies' => $companies,
            'queuedEmails' => $queuedEmails,
            'industries' => $industries,
            'jobTitles' => $jobTitles,
            'tags' => $tags,
            'emailTemplates' => $emailTemplates,
        ]);
    }
}

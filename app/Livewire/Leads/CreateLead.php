<?php

namespace App\Livewire\Leads;

use App\Jobs\ProcessLeadDiscovery;
use App\Models\LeadRequest;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Create Lead Request'])]
class CreateLead extends Component
{
    public string $reference_company_name = '';
    public ?string $reference_company_url = null;
    public int $target_count = 10;
    public array $target_job_titles = ['CEO', 'CFO'];
    public string $new_job_title = '';
    public ?string $country = null;

    public function addJobTitle(): void
    {
        if (!empty($this->new_job_title) && !in_array($this->new_job_title, $this->target_job_titles)) {
            $this->target_job_titles[] = trim($this->new_job_title);
            $this->new_job_title = '';
        }
    }

    public function removeJobTitle(string $title): void
    {
        $this->target_job_titles = array_values(array_filter($this->target_job_titles, fn($t) => $t !== $title));
    }

    public function create(): void
    {
        $validated = $this->validate([
            'reference_company_name' => 'required|string|max:255',
            'reference_company_url' => 'nullable|url|max:500',
            'target_count' => 'required|integer|min:1|max:100',
            'target_job_titles' => 'required|array|min:1',
            'target_job_titles.*' => 'string|max:100',
            'country' => 'nullable|string|max:2', // ISO country code (2 letters)
        ]);

        $leadRequest = LeadRequest::create([
            'user_id' => auth()->id(),
            'reference_company_name' => $validated['reference_company_name'],
            'reference_company_url' => $validated['reference_company_url'],
            'target_count' => $validated['target_count'],
            'target_job_titles' => $validated['target_job_titles'],
            'country' => $validated['country'] ?? null,
            'status' => 'pending',
        ]);

        Log::info('📋 New Lead Request Created', [
            'lead_request_id' => $leadRequest->id,
            'user_id' => auth()->id(),
            'company_name' => $validated['reference_company_name'],
            'company_url' => $validated['reference_company_url'],
            'target_count' => $validated['target_count'],
            'job_titles' => $validated['target_job_titles'],
        ]);

        ProcessLeadDiscovery::dispatch($leadRequest);

        Log::info('📤 ProcessLeadDiscovery Job Dispatched', [
            'lead_request_id' => $leadRequest->id,
        ]);

        session()->flash('message', 'Lead generation request created successfully! Processing will begin shortly.');

        $this->redirect(route('leads.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.leads.create-lead');
    }
}

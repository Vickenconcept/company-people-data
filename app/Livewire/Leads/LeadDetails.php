<?php

namespace App\Livewire\Leads;

use App\Models\LeadRequest;
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

    public function mount(int $id)
    {
        $this->leadRequest = LeadRequest::where('user_id', auth()->id())
            ->with(['leadResults.company', 'leadResults.person'])
            ->findOrFail($id);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage(); // Reset pagination when switching tabs
    }

    public function render()
    {
        $results = $this->leadRequest->leadResults()
            ->with(['company', 'person'])
            ->latest()
            ->paginate(20);

        // Get unique companies found for this lead request
        $companies = $this->leadRequest->leadResults()
            ->with('company')
            ->get()
            ->pluck('company')
            ->unique('id')
            ->values();

        return view('livewire.leads.lead-details', [
            'results' => $results,
            'companies' => $companies,
        ]);
    }
}

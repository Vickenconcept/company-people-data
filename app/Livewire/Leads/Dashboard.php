<?php

namespace App\Livewire\Leads;

use App\Models\LeadRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Lead Generation Dashboard'])]
class Dashboard extends Component
{
    use WithPagination;

    public array $selected = [];
    public bool $selectAll = false;
    public string $search = '';
    public ?string $statusFilter = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function toggleSelectAll()
    {
        $leadRequests = LeadRequest::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);
        
        $currentPageIds = $leadRequests->pluck('id')->toArray();

        if ($this->selectAll) {
            // Deselect all on current page
            $this->selected = array_values(array_diff($this->selected, $currentPageIds));
        } else {
            // Select all on current page
            $this->selected = array_values(array_unique(array_merge($this->selected, $currentPageIds)));
        }
        $this->selectAll = !$this->selectAll;
    }

    public function toggleSelect($id)
    {
        if (in_array($id, $this->selected)) {
            $this->selected = array_diff($this->selected, [$id]);
        } else {
            $this->selected[] = $id;
        }
        $this->selectAll = false;
    }

    public function delete($id)
    {
        $leadRequest = LeadRequest::where('user_id', auth()->id())->findOrFail($id);
        $leadRequest->delete();
        
        $this->selected = array_diff($this->selected, [$id]);
        session()->flash('message', 'Lead request deleted successfully.');
    }

    public function bulkDelete()
    {
        if (empty($this->selected)) {
            session()->flash('error', 'Please select at least one lead request to delete.');
            return;
        }

        $count = LeadRequest::where('user_id', auth()->id())
            ->whereIn('id', $this->selected)
            ->delete();

        $this->selected = [];
        $this->selectAll = false;
        session()->flash('message', "{$count} lead request(s) deleted successfully.");
    }

    public function updatedSelected()
    {
        // Reset selectAll when selection changes
        $this->selectAll = false;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    public function render()
    {
        $query = LeadRequest::where('user_id', auth()->id());

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('reference_company_name', 'like', '%' . $this->search . '%')
                  ->orWhere('reference_company_url', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Apply date filters
        if (!empty($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if (!empty($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $leadRequests = $query->withCount('leadResults')
            ->latest()
            ->paginate(10);

        // Check if all items on current page are selected
        $currentPageIds = $leadRequests->pluck('id')->toArray();
        $allSelected = !empty($currentPageIds) && count(array_intersect($currentPageIds, $this->selected)) === count($currentPageIds);
        if ($allSelected && !$this->selectAll) {
            $this->selectAll = true;
        } elseif (!$allSelected && $this->selectAll) {
            $this->selectAll = false;
        }

        // Enhanced stats with analytics
        $baseQuery = LeadRequest::where('user_id', auth()->id());
        
        // Total leads and contacts found
        $totalLeads = $baseQuery->count();
        $totalCompanies = $baseQuery->sum('companies_found');
        $totalContacts = $baseQuery->sum('contacts_found');
        
        // Status breakdown
        $stats = [
            'total' => $totalLeads,
            'completed' => $baseQuery->clone()->where('status', 'completed')->count(),
            'processing' => $baseQuery->clone()->where('status', 'processing')->count(),
            'pending' => $baseQuery->clone()->where('status', 'pending')->count(),
            'failed' => $baseQuery->clone()->where('status', 'failed')->count(),
            'total_companies' => $totalCompanies,
            'total_contacts' => $totalContacts,
        ];

        // Conversion analytics (from lead results)
        $leadResultsQuery = \App\Models\LeadResult::whereHas('leadRequest', function ($q) {
            $q->where('user_id', auth()->id());
        });
        
        $stats['conversion'] = [
            'total' => $leadResultsQuery->count(),
            'pending' => $leadResultsQuery->clone()->where('status', 'pending')->count(),
            'contacted' => $leadResultsQuery->clone()->where('status', 'contacted')->count(),
            'responded' => $leadResultsQuery->clone()->where('status', 'responded')->count(),
            'converted' => $leadResultsQuery->clone()->where('status', 'converted')->count(),
            'rejected' => $leadResultsQuery->clone()->where('status', 'rejected')->count(),
        ];

        // Calculate conversion rate
        if ($stats['conversion']['contacted'] > 0) {
            $stats['conversion_rate'] = round(
                ($stats['conversion']['converted'] / $stats['conversion']['contacted']) * 100,
                2
            );
        } else {
            $stats['conversion_rate'] = 0;
        }

        return view('livewire.leads.dashboard', [
            'leadRequests' => $leadRequests,
            'stats' => $stats,
        ]);
    }
}

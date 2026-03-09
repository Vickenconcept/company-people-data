<?php

namespace App\Livewire\Leads;

use App\Models\LeadRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'All Lead Requests'])]
class AllLeads extends Component
{
    use WithPagination;

    public array $selected = [];
    public bool $selectAll = false;
    public string $search = '';
    public ?string $statusFilter = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public array $stats = [];

    public function toggleSelectAll(): void
    {
        $leadRequests = $this->buildBaseQuery()
            ->latest()
            ->paginate(15);

        $currentPageIds = $leadRequests->pluck('id')->toArray();

        if ($this->selectAll) {
            $this->selected = array_values(array_diff($this->selected, $currentPageIds));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $currentPageIds)));
        }

        $this->selectAll = !$this->selectAll;
    }

    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selected)) {
            $this->selected = array_diff($this->selected, [$id]);
        } else {
            $this->selected[] = $id;
        }

        $this->selectAll = false;
    }

    public function delete(int $id): void
    {
        $leadRequest = LeadRequest::where('user_id', auth()->id())->findOrFail($id);
        $leadRequest->delete();

        $this->selected = array_diff($this->selected, [$id]);
        session()->flash('message', 'Lead request deleted successfully.');
    }

    public function bulkDelete(): void
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

    public function updatedSelected(): void
    {
        $this->selectAll = false;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    protected function computeStats(): void
    {
        $baseQuery = LeadRequest::where('user_id', auth()->id());

        $this->stats = [
            'total' => $baseQuery->count(),
            'completed' => $baseQuery->clone()->where('status', 'completed')->count(),
            'processing' => $baseQuery->clone()->where('status', 'processing')->count(),
            'pending' => $baseQuery->clone()->where('status', 'pending')->count(),
            'failed' => $baseQuery->clone()->where('status', 'failed')->count(),
            'total_companies' => $baseQuery->clone()->sum('companies_found'),
            'total_contacts' => $baseQuery->clone()->sum('contacts_found'),
        ];
    }

    protected function buildBaseQuery()
    {
        $query = LeadRequest::where('user_id', auth()->id());

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('reference_company_name', 'like', '%' . $this->search . '%')
                    ->orWhere('reference_company_url', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        if (!empty($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if (!empty($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query;
    }

    public function render()
    {
        $this->computeStats();

        $query = $this->buildBaseQuery();

        $leadRequests = $query->withCount('leadResults')
            ->latest()
            ->paginate(15);

        $currentPageIds = $leadRequests->pluck('id')->toArray();
        $allSelected = !empty($currentPageIds) && count(array_intersect($currentPageIds, $this->selected)) === count($currentPageIds);
        if ($allSelected && !$this->selectAll) {
            $this->selectAll = true;
        } elseif (!$allSelected && $this->selectAll) {
            $this->selectAll = false;
        }

        return view('livewire.leads.all-leads', [
            'leadRequests' => $leadRequests,
        ]);
    }
}


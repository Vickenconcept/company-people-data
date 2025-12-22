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

    public function render()
    {
        $leadRequests = LeadRequest::where('user_id', auth()->id())
            ->withCount('leadResults')
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

        $stats = [
            'total' => LeadRequest::where('user_id', auth()->id())->count(),
            'completed' => LeadRequest::where('user_id', auth()->id())->where('status', 'completed')->count(),
            'processing' => LeadRequest::where('user_id', auth()->id())->where('status', 'processing')->count(),
            'pending' => LeadRequest::where('user_id', auth()->id())->where('status', 'pending')->count(),
        ];

        return view('livewire.leads.dashboard', [
            'leadRequests' => $leadRequests,
            'stats' => $stats,
        ]);
    }
}

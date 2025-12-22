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

    public function render()
    {
        $leadRequests = LeadRequest::where('user_id', auth()->id())
            ->withCount('leadResults')
            ->latest()
            ->paginate(10);

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

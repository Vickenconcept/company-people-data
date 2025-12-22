<?php

namespace App\Livewire\Leads;

use App\Models\ApiKey;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'API Keys Management'])]
class ApiKeys extends Component
{
    public array $services = [
        'openai' => 'OpenAI',
        'scraperapi' => 'ScraperAPI',
        'apollo' => 'Apollo.io',
        'hunter' => 'Hunter.io',
    ];

    public string $selected_service = '';
    public string $api_key = '';
    public bool $is_active = true;
    public ?int $editing_id = null;

    public function mount(): void
    {
        $this->selected_service = 'openai';
    }

    public function edit(int $id): void
    {
        $apiKey = ApiKey::where('user_id', auth()->id())->findOrFail($id);
        $this->editing_id = $apiKey->id;
        $this->selected_service = $apiKey->service;
        $this->api_key = $apiKey->api_key;
        $this->is_active = $apiKey->is_active;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editing_id', 'api_key', 'is_active']);
        $this->selected_service = 'openai';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'selected_service' => 'required|in:openai,scraperapi,apollo,hunter',
            'api_key' => 'required|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($this->editing_id) {
            $apiKey = ApiKey::where('user_id', auth()->id())
                ->findOrFail($this->editing_id);
            $apiKey->update($validated);
            session()->flash('message', 'API key updated successfully!');
        } else {
            ApiKey::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'service' => $validated['selected_service'],
                ],
                [
                    'api_key' => $validated['api_key'],
                    'is_active' => $validated['is_active'] ?? true,
                ]
            );
            session()->flash('message', 'API key saved successfully!');
        }

        $this->reset(['editing_id', 'api_key', 'is_active']);
        $this->selected_service = 'openai';
    }

    public function delete(int $id): void
    {
        $apiKey = ApiKey::where('user_id', auth()->id())->findOrFail($id);
        $apiKey->delete();
        session()->flash('message', 'API key deleted successfully!');
    }

    public function render()
    {
        $apiKeys = ApiKey::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('livewire.leads.api-keys', [
            'apiKeys' => $apiKeys,
        ]);
    }
}

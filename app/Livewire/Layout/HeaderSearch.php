<?php

namespace App\Livewire\Layout;

use App\Services\HeaderSearchService;
use Livewire\Component;

class HeaderSearch extends Component
{
    public string $q = '';

    public bool $open = false;

    /** @var list<array{key: string, kind: string, title: string, meta: string, url: string}> */
    public array $items = [];

    public int $campaignOffset = 0;

    public int $companyOffset = 0;

    public bool $hasMore = false;

    public function openPanel(): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->open = true;
        $this->resetOffsets();
        $this->refreshItems();
    }

    public function closePanel(): void
    {
        $this->open = false;
    }

    public function updatedQ(): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->resetOffsets();
        $this->refreshItems();
    }

    public function loadMore(): void
    {
        if (! auth()->check() || ! $this->open || trim($this->q) === '' || ! $this->hasMore) {
            return;
        }

        $service = app(HeaderSearchService::class);
        $batch = $service->searchBatch(auth()->id(), trim($this->q), $this->campaignOffset, $this->companyOffset);

        $existing = array_flip(array_column($this->items, 'key'));
        foreach ($batch['items'] as $row) {
            if (! isset($existing[$row['key']])) {
                $this->items[] = $row;
                $existing[$row['key']] = true;
            }
        }

        $this->campaignOffset = $batch['next_campaign_offset'];
        $this->companyOffset = $batch['next_company_offset'];
        $this->hasMore = $batch['has_more'];
    }

    private function resetOffsets(): void
    {
        $this->campaignOffset = 0;
        $this->companyOffset = 0;
    }

    private function refreshItems(): void
    {
        $service = app(HeaderSearchService::class);
        $uid = auth()->id();
        $trimmed = trim($this->q);

        if ($trimmed === '') {
            $this->items = $service->initialMixed($uid);
            $this->hasMore = false;

            return;
        }

        $batch = $service->searchBatch($uid, $trimmed, 0, 0);
        $this->items = $batch['items'];
        $this->campaignOffset = $batch['next_campaign_offset'];
        $this->companyOffset = $batch['next_company_offset'];
        $this->hasMore = $batch['has_more'];
    }

    public function render()
    {
        return view('livewire.layout.header-search');
    }
}

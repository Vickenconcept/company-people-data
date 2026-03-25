<?php

namespace App\Services;

use App\Models\Company;
use App\Models\LeadRequest;
use App\Models\LeadResult;
use Illuminate\Support\Collection;

class HeaderSearchService
{
    public const INITIAL_LIMIT = 3;

    public const PER_TYPE_PAGE = 5;

    /**
     * @return list<array{key: string, kind: string, title: string, meta: string, url: string}>
     */
    public function initialMixed(int $userId): array
    {
        $campaigns = LeadRequest::query()
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->limit(3)
            ->get(['id', 'reference_company_name']);

        $company = Company::query()
            ->whereHas('leadResults.leadRequest', fn ($q) => $q->where('user_id', $userId))
            ->orderBy('name')
            ->first(['id', 'name']);

        $items = [];
        if ($campaigns->get(0)) {
            $items[] = $this->campaignRow($campaigns[0]);
        }
        if ($company) {
            $items[] = $this->companyRow($userId, $company);
        }
        if ($campaigns->get(1) && count($items) < self::INITIAL_LIMIT) {
            $items[] = $this->campaignRow($campaigns[1]);
        }
        if ($campaigns->get(2) && count($items) < self::INITIAL_LIMIT) {
            $items[] = $this->campaignRow($campaigns[2]);
        }

        return array_slice($items, 0, self::INITIAL_LIMIT);
    }

    /**
     * @return array{items: list<array>, next_campaign_offset: int, next_company_offset: int, has_more: bool}
     */
    public function searchBatch(int $userId, string $query, int $campaignOffset, int $companyOffset): array
    {
        $like = $this->likePattern($query);

        $campaigns = LeadRequest::query()
            ->where('user_id', $userId)
            ->where('reference_company_name', 'like', $like)
            ->latest('updated_at')
            ->offset($campaignOffset)
            ->limit(self::PER_TYPE_PAGE)
            ->get(['id', 'reference_company_name']);

        $companies = Company::query()
            ->whereHas('leadResults.leadRequest', fn ($q) => $q->where('user_id', $userId))
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->offset($companyOffset)
            ->limit(self::PER_TYPE_PAGE)
            ->get(['id', 'name']);

        $items = $this->interleaveRows($userId, $campaigns, $companies);

        $nextCampaign = $campaignOffset + $campaigns->count();
        $nextCompany = $companyOffset + $companies->count();
        $hasMore = $campaigns->count() === self::PER_TYPE_PAGE
            || $companies->count() === self::PER_TYPE_PAGE;

        return [
            'items' => $items,
            'next_campaign_offset' => $nextCampaign,
            'next_company_offset' => $nextCompany,
            'has_more' => $hasMore,
        ];
    }

    /**
     * @param  Collection<int, LeadRequest>  $campaigns
     * @param  Collection<int, Company>  $companies
     * @return list<array{key: string, kind: string, title: string, meta: string, url: string}>
     */
    private function interleaveRows(int $userId, Collection $campaigns, Collection $companies): array
    {
        $maxRows = self::PER_TYPE_PAGE * 2;
        $items = [];
        $ci = 0;
        $coi = 0;

        while (count($items) < $maxRows && ($ci < $campaigns->count() || $coi < $companies->count())) {
            if ($ci < $campaigns->count()) {
                $items[] = $this->campaignRow($campaigns[$ci]);
                $ci++;
            }
            if (count($items) >= $maxRows) {
                break;
            }
            if ($coi < $companies->count()) {
                $items[] = $this->companyRow($userId, $companies[$coi]);
                $coi++;
            }
        }

        return $items;
    }

    private function campaignRow(LeadRequest $lr): array
    {
        $title = $lr->reference_company_name ?: __('Untitled campaign');

        return [
            'key' => 'c-'.$lr->id,
            'kind' => 'campaign',
            'title' => $title,
            'meta' => __('Campaign'),
            'url' => route('leads.details', $lr->id),
        ];
    }

    private function companyRow(int $userId, Company $company): array
    {
        $leadRequestId = LeadResult::query()
            ->where('company_id', $company->id)
            ->whereHas('leadRequest', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('id')
            ->value('lead_request_id');

        $url = $leadRequestId
            ? route('leads.details', $leadRequestId)
            : route('leads.all');

        return [
            'key' => 'co-'.$company->id,
            'kind' => 'company',
            'title' => $company->name,
            'meta' => __('Company'),
            'url' => $url,
        ];
    }

    private function likePattern(string $query): string
    {
        $escaped = addcslashes(trim($query), '%_\\');

        return '%'.$escaped.'%';
    }
}

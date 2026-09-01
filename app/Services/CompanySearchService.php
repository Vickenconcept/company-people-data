<?php

namespace App\Services;

use App\Contracts\ProspectingProvider;
use App\Models\Company;
use App\Models\User;
use App\Services\Prospecting\ProspectingProviderFactory;
use Illuminate\Support\Facades\Log;

class CompanySearchService
{
    protected ProspectingProvider $provider;

    public function __construct(?ProspectingProvider $provider = null)
    {
        $this->provider = $provider ?? ProspectingProviderFactory::make();
    }

    public function setApiKeyFromUser(User $user): self
    {
        $apiKey = $user->apiKeys()
            ->where('service', $this->provider->getName())
            ->where('is_active', true)
            ->first();

        if ($apiKey) {
            $this->provider->setApiKey($apiKey->api_key);
        }

        return $this;
    }

    public function searchCompanies(array $criteria, int $limit = 10, ?string $referenceCompanyDomain = null): array
    {
        try {
            $result = $this->provider->searchCompanies($criteria, $limit, $referenceCompanyDomain);

            if (!$result['success']) {
                return $result;
            }

            $companies = $result['companies'] ?? [];
            $expectedIndustry = strtolower($criteria['industry'] ?? '');
            $isTechSearch = str_contains($expectedIndustry, 'technology')
                || str_contains($expectedIndustry, 'software')
                || str_contains($expectedIndustry, 'information technology');

            if (!$isTechSearch) {
                $companies = $this->filterIrrelevantCompanies($companies, $criteria);
            }

            if (empty($companies)) {
                Log::warning('CompanySearchService: No relevant companies after filtering', [
                    'provider' => $this->provider->getName(),
                    'expected_industry' => $criteria['industry'] ?? 'N/A',
                ]);

                return [
                    'success' => true,
                    'companies' => [],
                    'warning' => 'Search returned results but none matched the target industry after filtering.',
                ];
            }

            Log::info('CompanySearchService: '.$this->provider->getName().' found '.count($companies).' companies');

            return [
                'success' => true,
                'companies' => $companies,
            ];
        } catch (\Exception $e) {
            Log::error('CompanySearchService: Exception', [
                'provider' => $this->provider->getName(),
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'companies' => [],
            ];
        }
    }

    /**
     * Filter out obviously irrelevant companies (tech giants when searching for non-tech industries).
     *
     * @param  array<int, array<string, mixed>>  $companies
     * @return array<int, array<string, mixed>>
     */
    protected function filterIrrelevantCompanies(array $companies, array $criteria): array
    {
        $irrelevantDefaults = ['google', 'amazon', 'linkedin', 'microsoft', 'apple', 'facebook', 'meta platforms'];

        return array_values(array_filter($companies, function ($company) use ($irrelevantDefaults) {
            $name = strtolower($company['name'] ?? '');
            foreach ($irrelevantDefaults as $irrelevant) {
                if ($name === $irrelevant || str_starts_with($name, $irrelevant.' ')) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $companies
     * @return array<int, Company>
     */
    public function storeCompanies(array $companies): array
    {
        $stored = [];

        foreach ($companies as $companyData) {
            $company = Company::updateOrCreate(
                [
                    'domain' => $companyData['domain'],
                    'external_id' => $companyData['external_id'],
                    'data_source' => $companyData['data_source'],
                ],
                $companyData
            );

            $stored[] = $company;
        }

        return $stored;
    }
}

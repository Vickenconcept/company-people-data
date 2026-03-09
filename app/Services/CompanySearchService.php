<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CompanySearchService
{
    protected string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.apollo.api_key');
    }

    /**
     * Set API key from user's stored keys
     */
    public function setApiKeyFromUser(User $user): self
    {
        $apiKey = $user->apiKeys()
            ->where('service', 'apollo')
            ->where('is_active', true)
            ->first();

        if ($apiKey) {
            $this->apiKey = $apiKey->api_key;
        }

        return $this;
    }

    /**
     * Search for companies based on criteria using Apollo
     * Uses POST /v1/mixed_companies/search (Apollo's proper search endpoint)
     */
    public function searchCompanies(array $criteria, int $limit = 10, ?string $referenceCompanyDomain = null): array
    {
        try {
            // Build POST body for Apollo's mixed_companies/search endpoint
            $body = [
                'page' => 1,
                'per_page' => $limit,
            ];

            // --- Organization locations (Apollo accepts full country names in an array) ---
            $locations = [];
            if (!empty($criteria['countries']) && is_array($criteria['countries'])) {
                foreach (array_slice($criteria['countries'], 0, 5) as $country) {
                    if (!empty($country)) {
                        $locations[] = $country;
                    }
                }
            } elseif (!empty($criteria['country'])) {
                $locations[] = $criteria['country'];
            }
            if (!empty($locations)) {
                $body['organization_locations'] = $locations;
            }

            // --- Industry keyword tags (Apollo accepts string arrays) ---
            $industryTags = [];
            if (!empty($criteria['industries']) && is_array($criteria['industries'])) {
                $industryTags = array_slice($criteria['industries'], 0, 5);
            }
            if (!empty($criteria['industry']) && !in_array($criteria['industry'], $industryTags)) {
                array_unshift($industryTags, $criteria['industry']);
            }
            if (!empty($industryTags)) {
                $body['q_organization_keyword_tags'] = $industryTags;
            }

            // --- Employee count ranges ---
            if (!empty($criteria['company_size_min']) || !empty($criteria['company_size_max'])) {
                $min = $criteria['company_size_min'] ?? 1;
                $max = $criteria['company_size_max'] ?? 10000;
                // Apollo expects comma-separated ranges like "11,50" or "201,500"
                $body['organization_num_employees_ranges'] = ["{$min},{$max}"];
            }

            // --- Keywords for organization search ---
            if (!empty($criteria['keywords']) && is_array($criteria['keywords'])) {
                // Use top 5 most relevant keywords, keep it focused
                $keywords = array_slice(array_filter(array_unique($criteria['keywords'])), 0, 5);
                if (!empty($keywords)) {
                    $body['q_organization_keyword_tags'] = array_merge(
                        $body['q_organization_keyword_tags'] ?? [],
                        $keywords
                    );
                    // Remove duplicates
                    $body['q_organization_keyword_tags'] = array_values(array_unique($body['q_organization_keyword_tags']));
                }
            }

            // Apollo organization search requires POST with X-Api-Key header
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Cache-Control' => 'no-cache',
                ])
                ->post('https://api.apollo.io/api/v1/mixed_companies/search', $body);

            if ($response->successful()) {
                $data = $response->json();
                $companies = $data['organizations'] ?? $data['accounts'] ?? [];

                // Filter out the reference company itself and obvious tech giants
                // only if the target industry is NOT tech
                $expectedIndustry = strtolower($criteria['industry'] ?? '');
                $isTechSearch = str_contains($expectedIndustry, 'technology') || str_contains($expectedIndustry, 'software') || str_contains($expectedIndustry, 'information technology');

                if (!$isTechSearch) {
                    $companies = $this->filterIrrelevantCompanies($companies, $criteria);
                }

                // If after filtering we have no companies, log a warning but still return what we have
                if (empty($companies)) {
                    Log::warning('⚠️ CompanySearchService: No relevant companies after filtering', [
                        'expected_industry' => $criteria['industry'] ?? 'N/A',
                        'raw_count' => count($data['organizations'] ?? $data['accounts'] ?? []),
                        'suggestion' => 'Try broadening search criteria or adjusting keywords.',
                    ]);

                    return [
                        'success' => true,
                        'companies' => [],
                        'warning' => 'Apollo returned results but none matched the target industry after filtering.',
                    ];
                }

                Log::info('✅ CompanySearchService: ' . count($companies) . ' companies found');

                return [
                    'success' => true,
                    'companies' => array_map([$this, 'mapApolloCompany'], $companies),
                ];
            }

            Log::error('❌ CompanySearchService: Apollo API failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to search companies: HTTP ' . $response->status(),
                'companies' => [],
            ];
        } catch (\Exception $e) {
            Log::error('❌ CompanySearchService: Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'companies' => [],
            ];
        }
    }

    /**
     * Map Apollo company data to our format
     * Handles both organizations/search and mixed_companies/search response formats
     */
    protected function mapApolloCompany(array $data): array
    {
        // Apollo may return website_url or primary_domain depending on the endpoint
        $websiteUrl = $data['website_url'] ?? $data['primary_domain'] ?? null;
        $domain = null;
        if ($websiteUrl) {
            $domain = parse_url($websiteUrl, PHP_URL_HOST) ?? $websiteUrl;
            // If primary_domain was used, it won't have a scheme
            $domain = str_replace(['http://', 'https://', 'www.'], '', $domain);
        }

        return [
            'name' => $data['name'] ?? '',
            'domain' => $domain,
            'website' => $websiteUrl ? (str_starts_with($websiteUrl, 'http') ? $websiteUrl : 'https://' . $websiteUrl) : null,
            'industry' => $data['industry'] ?? null,
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'description' => $data['short_description'] ?? null,
            'employee_count' => $data['estimated_num_employees'] ?? null,
            'revenue' => null,
            'founded_year' => $data['founded_year'] ?? null,
            'technologies' => [],
            'metadata' => $data,
            'data_source' => 'apollo',
            'external_id' => (string) ($data['id'] ?? null),
        ];
    }

    /**
     * Filter out obviously irrelevant companies (tech giants when searching for non-tech industries)
     */
    protected function filterIrrelevantCompanies(array $companies, array $criteria): array
    {
        $irrelevantDefaults = ['google', 'amazon', 'linkedin', 'microsoft', 'apple', 'facebook', 'meta platforms'];

        return array_values(array_filter($companies, function ($company) use ($irrelevantDefaults) {
            $name = strtolower($company['name'] ?? '');
            foreach ($irrelevantDefaults as $irrelevant) {
                if ($name === $irrelevant || str_starts_with($name, $irrelevant . ' ')) {
                    return false;
                }
            }
            return true;
        }));
    }

    /**
     * Store companies in database
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

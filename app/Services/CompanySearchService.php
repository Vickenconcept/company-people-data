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
     */
    public function searchCompanies(array $criteria, int $limit = 10): array
    {
        Log::info('🏢 CompanySearchService: Starting company search', [
            'criteria' => $criteria,
            'limit' => $limit,
            'has_api_key' => !empty($this->apiKey),
        ]);

        try {
            $query = [
                'page' => 1,
                'per_page' => $limit,
            ];

            if (!empty($criteria['industry'])) {
                $query['industry_tag_ids'] = $criteria['industry'];
            }

            if (!empty($criteria['country'])) {
                $query['country'] = $criteria['country'];
            }

            if (!empty($criteria['employee_count_min'])) {
                $query['organization_num_employees_ranges'] = [
                    $criteria['employee_count_min'] . '-' . ($criteria['employee_count_max'] ?? 10000),
                ];
            }

            if (!empty($criteria['keywords'])) {
                $query['q_keywords'] = implode(' ', $criteria['keywords']);
            }

            Log::info('🏢 CompanySearchService: Sending request to Apollo', [
                'query_params' => $query,
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get('https://api.apollo.io/v1/organizations/search', $query);

            Log::info('🏢 CompanySearchService: Received response from Apollo', [
                'status' => $response->status(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $companies = $data['organizations'] ?? [];

                Log::info('✅ CompanySearchService: Companies found', [
                    'count' => count($companies),
                    'total_available' => $data['pagination']['total_entries'] ?? 0,
                ]);

                return [
                    'success' => true,
                    'companies' => array_map([$this, 'mapApolloCompany'], $companies),
                ];
            }

            Log::error('❌ CompanySearchService: Apollo API Error', [
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
     */
    protected function mapApolloCompany(array $data): array
    {
        return [
            'name' => $data['name'] ?? '',
            'domain' => parse_url($data['website_url'] ?? '', PHP_URL_HOST) ?? null,
            'website' => $data['website_url'] ?? null,
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

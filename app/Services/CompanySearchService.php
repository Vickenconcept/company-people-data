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

            // Use industries - but Apollo might not accept all industry names
            // We'll try to use a generic industry name that Apollo recognizes
            // If the industry is too specific, we'll skip it and rely on keywords
            $industryToUse = null;
            if (!empty($criteria['industry'])) {
                $industryToUse = $criteria['industry'];
            } elseif (!empty($criteria['industries']) && is_array($criteria['industries'])) {
                $industryToUse = $criteria['industries'][0];
            }
            
            // Skip industry_tag_ids - Apollo might need numeric IDs, not names
            // Rely on keywords which are more flexible and reliable
            // This avoids 422 errors from invalid industry tag formats
            if ($industryToUse) {
                Log::info('🏢 CompanySearchService: Using keywords-based search (skipping industry_tag_ids for compatibility)', [
                    'industry' => $industryToUse,
                ]);
            }

            // Use countries array if available
            // Only add country if it's not empty (Apollo doesn't like empty strings)
            // Apollo expects 2-letter country codes (e.g., US, GB, FR), not full names
            if (!empty($criteria['countries']) && is_array($criteria['countries']) && count($criteria['countries']) > 0) {
                $country = $criteria['countries'][0] ?? $criteria['country'] ?? null;
                if (!empty($country)) {
                    // Convert full country names to codes if needed (e.g., "United States" -> "US")
                    $countryCode = $this->normalizeCountryCode($country);
                    if ($countryCode) {
                        $query['country'] = $countryCode;
                    }
                }
            } elseif (!empty($criteria['country'])) {
                $countryCode = $this->normalizeCountryCode($criteria['country']);
                if ($countryCode) {
                    $query['country'] = $countryCode;
                }
            }

            // Employee count range - Apollo might expect array format or specific ranges
            // Let's try without this parameter first, or use a simpler format
            // Apollo has predefined ranges, so we'll skip this if it causes issues
            // if (!empty($criteria['company_size_min'])) {
            //     $min = $criteria['company_size_min'];
            //     $max = $criteria['company_size_max'] ?? ($min * 10);
            //     // Try array format: ["1-10", "11-50"] or just skip for now
            // }

            // Combine keywords more effectively - Apollo has strict length limits
            if (!empty($criteria['keywords']) && is_array($criteria['keywords'])) {
                // Use top 5 most relevant keywords to keep query manageable
                // Apollo seems to have a limit around 150-200 characters
                $keywords = array_slice($criteria['keywords'], 0, 5);
                
                // Build keyword string with most specific terms first
                $keywordString = implode(' ', $keywords);
                // Limit to 150 characters to be safe
                $query['q_keywords'] = mb_substr($keywordString, 0, 150);
            } elseif (!empty($criteria['keywords'])) {
                $keywordsStr = is_array($criteria['keywords']) ? implode(' ', array_slice($criteria['keywords'], 0, 5)) : $criteria['keywords'];
                // Limit keyword string length to 150 chars
                $query['q_keywords'] = mb_substr($keywordsStr, 0, 150);
            }

            // Add industry name to keywords if not already present (but keep it short)
            if (!empty($criteria['industry']) && !empty($query['q_keywords'])) {
                $industryLower = strtolower($criteria['industry']);
                $keywordsLower = strtolower($query['q_keywords']);
                if (!str_contains($keywordsLower, $industryLower)) {
                    // Only add if we have room (keep total under 150)
                    $newLength = strlen($query['q_keywords']) + 1 + strlen($criteria['industry']);
                    if ($newLength <= 150) {
                        $query['q_keywords'] = trim($query['q_keywords'] . ' ' . $criteria['industry']);
                    }
                }
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

                // Log what Apollo returned before filtering
                Log::info('🔍 CompanySearchService: Apollo returned companies (before filtering)', [
                    'count' => count($companies),
                    'total_available' => $data['pagination']['total_entries'] ?? 0,
                    'sample_companies' => array_map(function($c) {
                        return [
                            'name' => $c['name'] ?? 'N/A',
                            'industry' => $c['industry'] ?? 'N/A',
                            'domain' => $c['website_url'] ?? 'N/A',
                        ];
                    }, array_slice($companies, 0, 5)),
                ]);

                // Filter out generic tech giants if they don't match the industry
                $companies = $this->filterIrrelevantCompanies($companies, $criteria);

                Log::info('✅ CompanySearchService: Companies found', [
                    'count' => count($companies),
                    'total_available' => $data['pagination']['total_entries'] ?? 0,
                    'after_filtering' => count($companies),
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
     * Filter out irrelevant companies - REMOVED: No filtering, return all companies
     * Apollo's search should handle relevance based on keywords
     */
    protected function filterIrrelevantCompanies(array $companies, array $criteria): array
    {
        // Return all companies - let Apollo's search algorithm determine relevance
        // User can see all results and decide what's relevant
        return $companies;
    }

    /**
     * Normalize country code - convert full country names to 2-letter ISO codes
     */
    protected function normalizeCountryCode(?string $country): ?string
    {
        if (empty($country)) {
            return null;
        }

        // If it's already a 2-letter code, return as-is (uppercase)
        if (strlen($country) === 2) {
            return strtoupper($country);
        }

        // Map common country names to ISO codes
        $countryMap = [
            'united states' => 'US',
            'united kingdom' => 'GB',
            'great britain' => 'GB',
            'germany' => 'DE',
            'france' => 'FR',
            'australia' => 'AU',
            'canada' => 'CA',
            'japan' => 'JP',
            'china' => 'CN',
            'india' => 'IN',
            'spain' => 'ES',
            'italy' => 'IT',
            'netherlands' => 'NL',
            'brazil' => 'BR',
            'mexico' => 'MX',
            'south korea' => 'KR',
            'singapore' => 'SG',
        ];

        $countryLower = strtolower(trim($country));
        
        // Check if it's in our map
        if (isset($countryMap[$countryLower])) {
            return $countryMap[$countryLower];
        }

        // If not found, return null (don't use invalid country codes)
        return null;
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

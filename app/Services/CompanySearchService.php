<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CompanySearchService
{
    protected string $apiKey;
    protected ?string $hunterApiKey = null;
    protected ?User $user = null;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.apollo.api_key');
    }

    /**
     * Set API key from user's stored keys
     */
    public function setApiKeyFromUser(User $user): self
    {
        $this->user = $user;
        
        // Get Apollo API key
        $apiKey = $user->apiKeys()
            ->where('service', 'apollo')
            ->where('is_active', true)
            ->first();

        if ($apiKey) {
            $this->apiKey = $apiKey->api_key;
        }

        // Get Hunter.io API key for fallback
        $hunterKey = $user->apiKeys()
            ->where('service', 'hunter')
            ->where('is_active', true)
            ->first();

        if ($hunterKey) {
            $this->hunterApiKey = $hunterKey->api_key;
        } elseif (config('services.hunter.api_key')) {
            $this->hunterApiKey = config('services.hunter.api_key');
        }

        return $this;
    }

    /**
     * Search for companies based on criteria
     * If Hunter.io is available, enriches reference company FIRST to improve search
     * Then tries Apollo with refined criteria
     */
    public function searchCompanies(array $criteria, int $limit = 10, ?string $referenceCompanyDomain = null): array
    {
        Log::info('🏢 CompanySearchService: Starting company search', [
            'criteria' => $criteria,
            'limit' => $limit,
            'has_api_key' => !empty($this->apiKey),
            'has_hunter_key' => !empty($this->hunterApiKey),
        ]);

        // STEP 1: If we have Hunter.io and a reference company, enrich it FIRST
        // This gives us better data to search with, avoiding wasted Apollo credits
        if (!empty($this->hunterApiKey) && $referenceCompanyDomain) {
            Log::info('🔍 CompanySearchService: Enriching reference company with Hunter.io FIRST', [
                'domain' => $referenceCompanyDomain,
            ]);
            
            $referenceCompany = $this->getCompanyFromHunter($referenceCompanyDomain);
            
            if ($referenceCompany) {
                Log::info('✅ CompanySearchService: Reference company enriched', [
                    'name' => $referenceCompany['name'] ?? 'N/A',
                    'industry' => $referenceCompany['industry'] ?? 'N/A',
                    'country' => $referenceCompany['country'] ?? 'N/A',
                ]);
                
                // Refine criteria with Hunter.io data BEFORE searching
                $criteria = $this->refineCriteriaWithHunterData($criteria, $referenceCompany);
                
                Log::info('🔍 CompanySearchService: Criteria refined with Hunter.io data', [
                    'refined_industry' => $criteria['industry'] ?? 'N/A',
                    'refined_country' => $criteria['country'] ?? 'N/A',
                ]);
            }
        }

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
            // Prioritize most specific keywords first (from Hunter.io enrichment)
            if (!empty($criteria['keywords']) && is_array($criteria['keywords'])) {
                // Use top 7 most relevant keywords (increased from 5 for better specificity)
                // Apollo seems to have a limit around 150-200 characters
                $keywords = array_slice($criteria['keywords'], 0, 7);
                
                // Build keyword string with most specific terms first
                // Remove duplicates and empty values
                $keywords = array_filter(array_unique($keywords));
                $keywordString = implode(' ', $keywords);
                
                // Limit to 150 characters to be safe
                $query['q_keywords'] = mb_substr($keywordString, 0, 150);
            } elseif (!empty($criteria['keywords'])) {
                $keywordsStr = is_array($criteria['keywords']) ? implode(' ', array_slice($criteria['keywords'], 0, 7)) : $criteria['keywords'];
                // Limit keyword string length to 150 chars
                $query['q_keywords'] = mb_substr($keywordsStr, 0, 150);
            }

            // Add industry name to keywords if not already present (but keep it short)
            // This helps Apollo understand the search better
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
            
            // Log the final query to help debug Apollo free plan limitations
            Log::info('🔍 CompanySearchService: Final Apollo query', [
                'q_keywords' => $query['q_keywords'] ?? 'N/A',
                'country' => $query['country'] ?? 'N/A',
                'per_page' => $query['per_page'],
            ]);

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

                // Check if results are relevant
                // Since we already enriched with Hunter.io above, if results are still bad,
                // it means Apollo free plan is completely broken - reject the results
                $isRelevant = $this->areResultsRelevant($companies, $criteria);
                
                if (!$isRelevant) {
                    Log::error('❌ CompanySearchService: Apollo free plan returned completely irrelevant results', [
                        'apollo_count' => count($companies),
                        'expected_industry' => $criteria['industry'] ?? 'N/A',
                        'sample_companies' => array_slice(array_map(fn($c) => $c['name'] ?? 'N/A', $companies), 0, 5),
                        'note' => 'Apollo free plan is ignoring search criteria. Rejecting results to save credits.',
                    ]);
                    
                    // REJECT the results - don't waste credits on person lookup for irrelevant companies
                    return [
                        'success' => false,
                        'error' => 'Apollo free plan returned irrelevant results (Google, Amazon, LinkedIn, etc.) that do not match your search criteria. This is a limitation of Apollo\'s free plan. Please upgrade to a paid plan or use Hunter.io for company enrichment only.',
                        'companies' => [],
                        'apollo_free_plan_limited' => true,
                    ];
                }

                Log::info('✅ CompanySearchService: Companies found', [
                    'count' => count($companies),
                    'total_available' => $data['pagination']['total_entries'] ?? 0,
                    'after_filtering' => count($companies),
                    'source' => 'apollo',
                ]);

                return [
                    'success' => true,
                    'companies' => array_map([$this, 'mapApolloCompany'], $companies),
                ];
            }

            // Apollo failed - we already tried Hunter.io enrichment above
            // If we're here, Apollo API failed completely (not just bad results)
            Log::error('❌ CompanySearchService: Apollo API failed', [
                'status' => $response->status(),
                'note' => 'Hunter.io enrichment was already attempted if available',
            ]);

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
     * Search for companies using Hunter.io
     * Uses company enrichment + search based on similar attributes
     */
    protected function searchWithHunter(array $criteria, int $limit, ?string $referenceCompanyDomain = null): array
    {
        Log::info('🔍 CompanySearchService: Starting Hunter.io company search', [
            'criteria' => $criteria,
            'limit' => $limit,
            'reference_domain' => $referenceCompanyDomain,
        ]);

        try {
            // If we have a reference company domain, try to get its details first
            $referenceCompany = null;
            if ($referenceCompanyDomain) {
                $referenceCompany = $this->getCompanyFromHunter($referenceCompanyDomain);
            }

            // Build search query using criteria
            // Hunter.io Discover API uses filters similar to their web interface
            $queryParams = [
                'api_key' => $this->hunterApiKey,
                'limit' => min($limit, 100), // Hunter.io typically limits to 100
            ];

            // Add industry filter if available
            if (!empty($criteria['industry'])) {
                $queryParams['industry'] = $criteria['industry'];
            } elseif (!empty($criteria['industries']) && is_array($criteria['industries'])) {
                $queryParams['industry'] = $criteria['industries'][0];
            }

            // Add country filter
            if (!empty($criteria['countries']) && is_array($criteria['countries']) && count($criteria['countries']) > 0) {
                $country = $criteria['countries'][0] ?? $criteria['country'] ?? null;
                if (!empty($country)) {
                    $queryParams['country'] = $this->normalizeCountryCode($country);
                }
            } elseif (!empty($criteria['country'])) {
                $countryCode = $this->normalizeCountryCode($criteria['country']);
                if ($countryCode) {
                    $queryParams['country'] = $countryCode;
                }
            }

            // Add keywords - Hunter.io supports keyword search
            if (!empty($criteria['keywords']) && is_array($criteria['keywords'])) {
                $keywords = array_slice($criteria['keywords'], 0, 5);
                $queryParams['keywords'] = implode(',', $keywords);
            }

            // If we have reference company data, use it to find similar companies
            if ($referenceCompany && !empty($referenceCompany['industry'])) {
                // Use reference company's industry if available
                if (empty($queryParams['industry'])) {
                    $queryParams['industry'] = $referenceCompany['industry'];
                }
            }

            Log::info('🔍 CompanySearchService: Sending request to Hunter.io', [
                'query_params' => array_merge($queryParams, ['api_key' => '***']),
            ]);

            // Hunter.io doesn't have a direct "companies search" API endpoint
            // Instead, we'll use company enrichment to get reference company details
            // and then use those details to improve our search criteria
            // For now, we'll return a message indicating Hunter.io company search
            // would require their web interface or a different approach
            
            // Try to use reference company data to refine search
            if ($referenceCompany) {
                Log::info('✅ CompanySearchService: Using Hunter.io enriched company data', [
                    'reference_company' => $referenceCompany['name'] ?? 'N/A',
                    'industry' => $referenceCompany['industry'] ?? 'N/A',
                ]);
                
                // Use the enriched company data to refine criteria
                if (!empty($referenceCompany['industry']) && empty($queryParams['industry'])) {
                    $queryParams['industry'] = $referenceCompany['industry'];
                }
            }

            // Since Hunter.io doesn't have a companies search API endpoint,
            // we'll use an alternative approach: search using keywords and industry
            // via a combination of their available endpoints
            // For now, return that we need to use Apollo with refined criteria
            Log::info('ℹ️ CompanySearchService: Hunter.io company search requires web interface', [
                'note' => 'Hunter.io Discover feature is web-only. Using enriched data to refine Apollo search.',
            ]);

            // Return empty result - the fallback will handle retrying with Apollo
            return [
                'success' => false,
                'error' => 'Hunter.io company search API not available. Using enriched data to refine search.',
                'companies' => [],
                'refined_criteria' => $queryParams,
            ];

        } catch (\Exception $e) {
            Log::error('❌ CompanySearchService: Hunter.io Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Hunter.io search failed: ' . $e->getMessage(),
                'companies' => [],
            ];
        }
    }

    /**
     * Get company details from Hunter.io using domain
     */
    protected function getCompanyFromHunter(string $domain): ?array
    {
        try {
            // Clean domain
            $domain = str_replace(['http://', 'https://', 'www.'], '', $domain);
            $domain = parse_url($domain, PHP_URL_HOST) ?? $domain;

            $response = Http::timeout(30)
                ->get('https://api.hunter.io/v2/company', [
                    'api_key' => $this->hunterApiKey,
                    'domain' => $domain,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ CompanySearchService: Failed to get company from Hunter.io', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Refine search criteria using Hunter.io enriched company data
     */
    protected function refineCriteriaWithHunterData(array $criteria, array $hunterCompany): array
    {
        $refined = $criteria;

        // Use Hunter.io company's industry if available and more specific
        if (!empty($hunterCompany['industry'])) {
            $refined['industry'] = $hunterCompany['industry'];
            if (empty($refined['industries']) || !is_array($refined['industries'])) {
                $refined['industries'] = [$hunterCompany['industry']];
            } else {
                // Add Hunter.io industry to the list if not already present
                if (!in_array($hunterCompany['industry'], $refined['industries'])) {
                    array_unshift($refined['industries'], $hunterCompany['industry']);
                }
            }
        }

        // Use Hunter.io company's country if available
        if (!empty($hunterCompany['country']) && empty($refined['country'])) {
            $refined['country'] = $hunterCompany['country'];
        }

        // Extract keywords from Hunter.io company description
        if (!empty($hunterCompany['description'])) {
            $keywords = $refined['keywords'] ?? [];
            if (!is_array($keywords)) {
                $keywords = [$keywords];
            }
            
            // Extract key terms from description (simple approach)
            $description = strtolower($hunterCompany['description']);
            $descriptionKeywords = [];
            
            // Look for common business terms
            $businessTerms = ['delivery', 'service', 'platform', 'solution', 'technology', 'software', 'app', 'marketplace'];
            foreach ($businessTerms as $term) {
                if (str_contains($description, $term) && !in_array($term, $keywords)) {
                    $descriptionKeywords[] = $term;
                }
            }
            
            // Merge description keywords with existing keywords
            $refined['keywords'] = array_merge($descriptionKeywords, $keywords);
        }

        // Add company type/sector from Hunter.io if available
        if (!empty($hunterCompany['type'])) {
            $keywords = $refined['keywords'] ?? [];
            if (!is_array($keywords)) {
                $keywords = [$keywords];
            }
            if (!in_array($hunterCompany['type'], $keywords)) {
                array_unshift($keywords, $hunterCompany['type']);
            }
            $refined['keywords'] = $keywords;
        }

        return $refined;
    }

    /**
     * Map Hunter.io company data to our format
     */
    protected function mapHunterCompany(array $data): array
    {
        return [
            'name' => $data['name'] ?? '',
            'domain' => $data['domain'] ?? parse_url($data['website'] ?? '', PHP_URL_HOST) ?? null,
            'website' => $data['website'] ?? ($data['domain'] ? "https://{$data['domain']}" : null),
            'industry' => $data['industry'] ?? null,
            'country' => $data['country'] ?? $data['headquarters_location'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'description' => $data['description'] ?? null,
            'employee_count' => $data['employees'] ?? $data['employee_count'] ?? null,
            'revenue' => $data['revenue'] ?? null,
            'founded_year' => $data['founded'] ?? $data['founded_year'] ?? null,
            'technologies' => $data['technologies'] ?? [],
            'metadata' => $data,
            'data_source' => 'hunter',
            'external_id' => (string) ($data['id'] ?? $data['domain'] ?? null),
        ];
    }

    /**
     * Check if Apollo results are relevant to the search criteria
     */
    protected function areResultsRelevant(array $companies, array $criteria): bool
    {
        if (empty($companies)) {
            return false;
        }

        // Check if results match the expected industry
        $expectedIndustry = strtolower($criteria['industry'] ?? '');
        $irrelevantCompanies = ['google', 'amazon', 'linkedin', 'microsoft', 'apple', 'facebook', 'meta'];

        $relevantCount = 0;
        foreach ($companies as $company) {
            $companyName = strtolower($company['name'] ?? '');
            $companyIndustry = strtolower($company['industry'] ?? '');

            // Skip if it's a generic tech giant
            $isIrrelevant = false;
            foreach ($irrelevantCompanies as $irrelevant) {
                if (str_contains($companyName, $irrelevant)) {
                    $isIrrelevant = true;
                    break;
                }
            }

            if ($isIrrelevant) {
                continue;
            }

            // Check if industry matches
            if (!empty($expectedIndustry) && !empty($companyIndustry)) {
                if (str_contains($companyIndustry, $expectedIndustry) || str_contains($expectedIndustry, $companyIndustry)) {
                    $relevantCount++;
                }
            } else {
                // If no industry specified, assume relevant
                $relevantCount++;
            }
        }

        // Consider results relevant if at least 50% match
        $relevanceRatio = $relevantCount / count($companies);
        return $relevanceRatio >= 0.5;
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

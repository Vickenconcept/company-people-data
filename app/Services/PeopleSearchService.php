<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PeopleSearchService
{
    protected string $apiKey;
    protected string $service = 'apollo'; // Default service
    protected ?User $user = null;

    public function __construct(?string $apiKey = null, string $service = 'apollo')
    {
        $this->apiKey = $apiKey ?? config("services.{$service}.api_key");
        $this->service = $service;
    }

    /**
     * Set API key from user's stored keys
     */
    public function setApiKeyFromUser(User $user, string $service = 'apollo'): self
    {
        $this->service = $service;
        $this->user = $user;
        $apiKey = $user->apiKeys()
            ->where('service', $service)
            ->where('is_active', true)
            ->first();

        if ($apiKey) {
            $this->apiKey = $apiKey->api_key;
        }

        return $this;
    }

    /**
     * Find people by company and job titles
     * Automatically falls back to Hunter.io if Apollo fails due to plan limitations
     */
    public function findPeople(Company $company, array $jobTitles, int $limit = 10): array
    {
        // Try Apollo first
        if ($this->service === 'apollo' || $this->service === 'auto') {
            $result = $this->findWithApollo($company, $jobTitles, $limit);
            
            // If Apollo fails due to plan limitations, try Hunter.io
            if (!$result['success'] || (isset($result['apollo_plan_limited']) && $result['apollo_plan_limited'])) {
                Log::info('🔄 PeopleSearchService: Apollo unavailable, falling back to Hunter.io', [
                    'company_id' => $company->id,
                    'reason' => $result['error'] ?? 'Plan limitation',
                ]);
                
                // Try to get Hunter API key from user or config
                $hunterResult = $this->tryHunterFallback($company, $jobTitles, $limit);
                if ($hunterResult['success']) {
                    return $hunterResult;
                }
            }
            
            return $result;
        }
        
        return match ($this->service) {
            'hunter' => $this->findWithHunter($company, $jobTitles, $limit),
            default => $this->findWithApollo($company, $jobTitles, $limit),
        };
    }
    
    /**
     * Try Hunter.io as fallback
     */
    protected function tryHunterFallback(Company $company, array $jobTitles, int $limit): array
    {
        $hunterKey = null;
        
        // Try to get Hunter API key from user first
        if ($this->user) {
            $hunterApiKey = $this->user->apiKeys()
                ->where('service', 'hunter')
                ->where('is_active', true)
                ->first();
            
            if ($hunterApiKey) {
                $hunterKey = $hunterApiKey->api_key;
            }
        }
        
        // Fallback to config
        if (empty($hunterKey)) {
            $hunterKey = config('services.hunter.api_key');
        }
        
        if (empty($hunterKey)) {
            Log::warning('⚠️ PeopleSearchService: Hunter.io API key not found', [
                'company_id' => $company->id,
            ]);
            return [
                'success' => false,
                'error' => 'Hunter.io API key not configured. Please add your Hunter.io API key in API Keys settings.',
                'people' => [],
            ];
        }
        
        $hunterService = new PeopleSearchService($hunterKey, 'hunter');
        return $hunterService->findWithHunter($company, $jobTitles, $limit);
    }

    /**
     * Find using Apollo
     */
    protected function findWithApollo(Company $company, array $jobTitles, int $limit): array
    {
        Log::info('👥 PeopleSearchService: Starting Apollo people search', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'job_titles' => $jobTitles,
            'limit' => $limit,
            'has_api_key' => !empty($this->apiKey),
        ]);

        try {
            $people = [];

            foreach ($jobTitles as $title) {
                Log::info('👥 PeopleSearchService: Searching for title', [
                    'company_id' => $company->id,
                    'title' => $title,
                ]);

                $response = Http::timeout(30)
                    ->withHeaders([
                        'X-Api-Key' => $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.apollo.io/v1/people/search', [
                        'organization_id' => $company->external_id,
                        'person_titles' => [$title],
                        'page' => 1,
                        'per_page' => $limit,
                    ]);

                Log::info('👥 PeopleSearchService: Received response', [
                    'company_id' => $company->id,
                    'title' => $title,
                    'status' => $response->status(),
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $found = $data['people'] ?? [];

                    Log::info('👥 PeopleSearchService: People found for title', [
                        'company_id' => $company->id,
                        'title' => $title,
                        'count' => count($found),
                    ]);

                    foreach ($found as $personData) {
                        $people[] = $this->mapApolloPerson($personData, $company->id);
                    }
                } else {
                    $responseBody = $response->json();
                    $errorCode = $responseBody['error_code'] ?? null;
                    
                    Log::warning('⚠️ PeopleSearchService: API request failed', [
                        'company_id' => $company->id,
                        'title' => $title,
                        'status' => $response->status(),
                        'error_code' => $errorCode,
                        'response_body' => substr($response->body(), 0, 500),
                    ]);
                    
                    // Check if it's a plan limitation error - return early to trigger fallback
                    if ($response->status() === 403 && $errorCode === 'API_INACCESSIBLE') {
                        Log::info('🔄 PeopleSearchService: Apollo plan limitation detected, will fallback to Hunter.io', [
                            'company_id' => $company->id,
                        ]);
                        return [
                            'success' => false,
                            'apollo_plan_limited' => true,
                            'error' => 'Apollo people search requires a paid plan. Falling back to Hunter.io.',
                            'people' => [],
                        ];
                    }
                }
            }

            Log::info('✅ PeopleSearchService: Search completed', [
                'company_id' => $company->id,
                'total_people_found' => count($people),
            ]);

            return [
                'success' => true,
                'people' => array_slice($people, 0, $limit),
            ];
        } catch (\Exception $e) {
            Log::error('❌ PeopleSearchService: Apollo Exception', [
                'company_id' => $company->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'people' => [],
            ];
        }
    }

    /**
     * Find using Hunter.io
     */
    protected function findWithHunter(Company $company, array $jobTitles, int $limit): array
    {
        Log::info('👥 PeopleSearchService: Starting Hunter.io search', [
            'company_id' => $company->id,
            'company_domain' => $company->domain,
            'job_titles' => $jobTitles,
            'limit' => $limit,
        ]);

        try {
            $people = [];

            // Clean domain (remove www. if present)
            $domain = $company->domain;
            if (str_starts_with($domain, 'www.')) {
                $domain = substr($domain, 4);
            }
            
            foreach ($jobTitles as $title) {
                $seniority = $this->mapTitleToSeniority($title);
                
                Log::info('👥 PeopleSearchService: Searching Hunter.io', [
                    'company_id' => $company->id,
                    'title' => $title,
                    'seniority' => $seniority,
                    'domain' => $domain,
                ]);

                // Build query parameters - only include seniority if it's valid
                $queryParams = [
                    'api_key' => $this->apiKey,
                    'domain' => $domain,
                    'limit' => $limit,
                ];
                
                // Only add seniority if it's a valid value (not 'employee')
                if ($seniority && $seniority !== 'employee') {
                    $queryParams['seniority'] = $seniority;
                }

                $response = Http::timeout(30)->get('https://api.hunter.io/v2/domain-search', $queryParams);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Hunter.io returns emails in 'data.emails' array
                    $found = $data['data']['emails'] ?? $data['emails'] ?? [];
                    $totalEmails = $data['meta']['results'] ?? $data['data']['meta']['results'] ?? count($found);

                    Log::info('✅ PeopleSearchService: Hunter.io results', [
                        'company_id' => $company->id,
                        'title' => $title,
                        'emails_found' => count($found),
                        'total_emails' => $totalEmails,
                        'response_structure' => array_keys($data),
                    ]);

                    // If we have emails, add them (with optional title filtering)
                    if (count($found) > 0) {
                        foreach ($found as $emailData) {
                            // Filter by job title if possible, but be lenient
                            $personTitle = strtolower($emailData['position'] ?? $emailData['title'] ?? '');
                            $searchTitle = strtolower($title);
                            
                            // Include if:
                            // 1. No position specified (include all)
                            // 2. Position matches search title
                            // 3. Search title is very short (like "md") - include all executives
                            if (empty($personTitle) || 
                                str_contains($personTitle, $searchTitle) || 
                                str_contains($searchTitle, $personTitle) ||
                                (strlen($searchTitle) <= 3 && !empty($personTitle))) {
                                $people[] = $this->mapHunterPerson($emailData, $company->id);
                            }
                        }
                    } else {
                        // Log if we expected emails but got none
                        Log::warning('⚠️ PeopleSearchService: Hunter.io returned 0 emails but total_emails > 0', [
                            'company_id' => $company->id,
                            'title' => $title,
                            'total_emails' => $totalEmails,
                            'response_sample' => substr(json_encode($data), 0, 500),
                        ]);
                    }
                } else {
                    $errorBody = $response->json();
                    Log::warning('⚠️ PeopleSearchService: Hunter.io request failed', [
                        'company_id' => $company->id,
                        'title' => $title,
                        'status' => $response->status(),
                        'error' => $errorBody['errors'][0]['details'] ?? $errorBody['errors'][0]['id'] ?? $response->body(),
                    ]);
                }
            }

            return [
                'success' => true,
                'people' => array_slice($people, 0, $limit),
            ];
        } catch (\Exception $e) {
            Log::error('❌ PeopleSearchService: Hunter.io Exception', [
                'company_id' => $company->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'people' => [],
            ];
        }
    }

    /**
     * Map Apollo person data
     */
    protected function mapApolloPerson(array $data, int $companyId): array
    {
        return [
            'company_id' => $companyId,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'full_name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
            'title' => $data['title'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone_numbers'][0]['number'] ?? null,
            'linkedin_url' => $data['linkedin_url'] ?? null,
            'twitter_handle' => $data['twitter_url'] ?? null,
            'bio' => null,
            'metadata' => $data,
            'data_source' => 'apollo',
            'external_id' => (string) ($data['id'] ?? null),
            'email_verified' => false,
        ];
    }

    /**
     * Map Hunter person data
     */
    protected function mapHunterPerson(array $data, int $companyId): array
    {
        return [
            'company_id' => $companyId,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'full_name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''),
            'title' => $data['position'] ?? null,
            'email' => $data['value'] ?? null,
            'phone' => null,
            'linkedin_url' => $data['linkedin'] ?? null,
            'twitter_handle' => null,
            'bio' => null,
            'metadata' => $data,
            'data_source' => 'hunter',
            'external_id' => $data['value'] ?? null,
            'email_verified' => ($data['verification']['status'] ?? null) === 'valid',
        ];
    }

    /**
     * Map job title to seniority level for Hunter.io
     * Valid values: executive, director, manager, senior, junior, or null
     */
    protected function mapTitleToSeniority(string $title): ?string
    {
        $title = strtolower($title);

        if (str_contains($title, 'ceo') || 
            str_contains($title, 'chief') || 
            str_contains($title, 'cfo') || 
            str_contains($title, 'cto') ||
            str_contains($title, 'president')) {
            return 'executive';
        }

        if (str_contains($title, 'director') || 
            str_contains($title, 'vp') || 
            str_contains($title, 'vice president')) {
            return 'director';
        }

        if (str_contains($title, 'manager') || 
            str_contains($title, 'head') ||
            str_contains($title, 'lead')) {
            return 'manager';
        }

        // Return null instead of 'employee' - Hunter.io doesn't accept 'employee'
        // We'll search without seniority filter for other titles
        return null;
    }

    /**
     * Store people in database
     */
    public function storePeople(array $people): array
    {
        $stored = [];

        foreach ($people as $personData) {
            $person = Person::updateOrCreate(
                [
                    'company_id' => $personData['company_id'],
                    'email' => $personData['email'],
                    'external_id' => $personData['external_id'],
                    'data_source' => $personData['data_source'],
                ],
                $personData
            );

            $stored[] = $person;
        }

        return $stored;
    }
}

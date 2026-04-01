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
        try {
            $people = [];
            $perTitleLimit = max(1, min($limit, 1));

            foreach ($jobTitles as $title) {
                $query = [
                    'page' => 1,
                    'per_page' => $perTitleLimit,
                ];

                // Use organization domain or name if we don't have an Apollo org ID
                if (!empty($company->external_id) && $company->data_source === 'apollo') {
                    $query['organization_ids'] = [$company->external_id];
                } elseif (!empty($company->domain)) {
                    $query['q_organization_domains'] = $company->domain;
                } else {
                    $query['q_organization_name'] = $company->name;
                }

                $query['person_titles'] = [$title];

                $response = Http::timeout(30)
                    ->withHeaders([
                        'X-Api-Key' => $this->apiKey,
                        'Content-Type' => 'application/json',
                        'Cache-Control' => 'no-cache',
                    ])
                    ->post('https://api.apollo.io/api/v1/mixed_people/api_search', $query);

                if ($response->successful()) {
                    $data = $response->json();
                    $found = $data['people'] ?? [];

                    foreach ($found as $personData) {
                        // If Apollo didn't return an email, try to reveal it
                        if (empty($personData['email']) && !empty($personData['id'])) {
                            $revealed = $this->revealApolloPersonEmail($personData);
                            if ($revealed) {
                                $personData = array_merge($personData, $revealed);
                            }
                        }
                        $people[] = $this->mapApolloPerson($personData, $company->id);
                    }

                    // Stop early once we have one contact with an email.
                    $firstWithEmail = array_values(array_filter(
                        $people,
                        fn (array $person): bool => !empty(trim((string) ($person['email'] ?? '')))
                    ));

                    if (!empty($firstWithEmail)) {
                        return [
                            'success' => true,
                            'people' => [array_slice($firstWithEmail, 0, 1)[0]],
                        ];
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

            // For contacts still missing email, try Hunter.io email finder
            $people = $this->enrichEmailsWithHunter($people, $company);

            $peopleWithEmail = array_values(array_filter(
                $people,
                fn (array $person): bool => !empty(trim((string) ($person['email'] ?? '')))
            ));

            return [
                'success' => true,
                'people' => array_slice($peopleWithEmail, 0, 1),
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
        try {
            $people = [];
            $perTitleLimit = max(1, min($limit, 1));

            // Clean domain (remove www. if present)
            $domain = $company->domain;
            if (str_starts_with($domain, 'www.')) {
                $domain = substr($domain, 4);
            }
            
            foreach ($jobTitles as $title) {
                $seniority = $this->mapTitleToSeniority($title);

                // Build query parameters - only include seniority if it's valid
                $queryParams = [
                    'api_key' => $this->apiKey,
                    'domain' => $domain,
                    'limit' => $perTitleLimit,
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

                                if (!empty(trim((string) ($emailData['value'] ?? '')))) {
                                    return [
                                        'success' => true,
                                        'people' => [array_slice($people, 0, 1)[0]],
                                    ];
                                }
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
                'people' => array_slice($people, 0, 1),
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
        // Check multiple possible email field locations
        $email = $data['email'] 
            ?? $data['contact_email'] 
            ?? $data['personal_emails'][0] ?? null;

        return [
            'company_id' => $companyId,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'full_name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
            'title' => $data['title'] ?? null,
            'email' => $email,
            'phone' => $data['phone_numbers'][0]['number'] ?? $data['phone_numbers'][0]['sanitized_number'] ?? null,
            'linkedin_url' => $data['linkedin_url'] ?? null,
            'twitter_handle' => $data['twitter_url'] ?? null,
            'bio' => null,
            'metadata' => $data,
            'data_source' => 'apollo',
            'external_id' => (string) ($data['id'] ?? null),
            'email_verified' => !empty($email),
        ];
    }

    /**
     * Reveal a person's email using Apollo's People Match endpoint
     * This costs 1 email credit per reveal
     */
    protected function revealApolloPersonEmail(array $personData): ?array
    {
        $personId = $personData['id'] ?? null;
        if (empty($personId)) {
            return null;
        }

        try {
            // Use Apollo's people/match endpoint to reveal contact info
            // Do NOT include reveal_phone_number as it requires a webhook_url
            $matchBody = [
                'id' => $personId,
                'reveal_personal_emails' => true,
            ];

            // Add name + organization for better matching
            if (!empty($personData['first_name']) && !empty($personData['last_name'])) {
                $matchBody['first_name'] = $personData['first_name'];
                $matchBody['last_name'] = $personData['last_name'];
                if (!empty($personData['organization']['name'])) {
                    $matchBody['organization_name'] = $personData['organization']['name'];
                }
                if (!empty($personData['organization']['primary_domain'])) {
                    $matchBody['domain'] = $personData['organization']['primary_domain'];
                }
            }

            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Cache-Control' => 'no-cache',
                ])
                ->post('https://api.apollo.io/api/v1/people/match', $matchBody);

            if ($response->successful()) {
                $result = $response->json();
                $person = $result['person'] ?? $result;
                $email = $person['email'] ?? $person['contact_email'] ?? null;
                $phone = $person['phone_numbers'][0]['number'] ?? $person['phone_numbers'][0]['sanitized_number'] ?? null;

                if (!empty($email) || !empty($phone)) {
                    return [
                        'email' => $email,
                        'phone_numbers' => $person['phone_numbers'] ?? $personData['phone_numbers'] ?? [],
                    ];
                }
            } else {
                Log::warning('⚠️ PeopleSearchService: Email reveal failed', [
                    'person_id' => $personId,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 300),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ PeopleSearchService: Email reveal exception', [
                'person_id' => $personId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Use Hunter.io to find emails for contacts that Apollo couldn't provide emails for.
     * Strategy:
     * 1. Do a single domain-search call to get all known emails for the company domain
     * 2. Match people without emails by first name to domain-search results
     * 3. For people with both first+last name still missing, try email-finder endpoint
     */
    protected function enrichEmailsWithHunter(array $people, Company $company): array
    {
        // Check if anyone actually needs an email
        $needsEmail = false;
        foreach ($people as $p) {
            if (empty($p['email'])) {
                $needsEmail = true;
                break;
            }
        }
        if (!$needsEmail) {
            return $people;
        }

        // Get Hunter.io API key
        $hunterKey = null;
        if ($this->user) {
            $hunterApiKey = $this->user->apiKeys()
                ->where('service', 'hunter')
                ->where('is_active', true)
                ->first();
            if ($hunterApiKey) {
                $hunterKey = $hunterApiKey->api_key;
            }
        }
        if (empty($hunterKey)) {
            $hunterKey = config('services.hunter.api_key');
        }
        if (empty($hunterKey)) {
            return $people;
        }

        // Clean domain
        $domain = $company->domain;
        if (empty($domain)) {
            return $people;
        }
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = rtrim($domain, '/');

        $emailsFound = 0;

        // Step 1: Domain search - get all known emails for this company (single API call)
        $domainEmails = [];
        try {
            $response = Http::timeout(15)->get('https://api.hunter.io/v2/domain-search', [
                'api_key' => $hunterKey,
                'domain' => $domain,
                'limit' => 50,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $domainEmails = $data['data']['emails'] ?? [];
            } else {
                Log::warning('⚠️ PeopleSearchService: Hunter.io domain-search failed', [
                    'domain' => $domain,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 200),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ PeopleSearchService: Hunter.io domain-search exception', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
        }

        // Step 2: Match people without emails to domain-search results by first name
        if (count($domainEmails) > 0) {
            foreach ($people as &$person) {
                if (!empty($person['email'])) {
                    continue;
                }
                if (empty($person['first_name'])) {
                    continue;
                }

                $firstName = strtolower(trim($person['first_name']));
                foreach ($domainEmails as $hunterEmail) {
                    $hunterFirst = strtolower(trim($hunterEmail['first_name'] ?? ''));
                    $hunterLast = strtolower(trim($hunterEmail['last_name'] ?? ''));
                    $personLast = strtolower(trim($person['last_name'] ?? ''));

                    // Match by first name (and last name if both have it)
                    $firstMatch = !empty($hunterFirst) && $hunterFirst === $firstName;
                    $lastMatch = empty($personLast) || empty($hunterLast) || $hunterLast === $personLast;

                    if ($firstMatch && $lastMatch && !empty($hunterEmail['value'])) {
                        $person['email'] = $hunterEmail['value'];
                        $person['email_verified'] = ($hunterEmail['verification']['status'] ?? '') === 'valid';
                        $person['data_source'] = 'apollo+hunter';
                        // Fill in last name if we got one from Hunter
                        if (empty($person['last_name']) && !empty($hunterEmail['last_name'])) {
                            $person['last_name'] = $hunterEmail['last_name'];
                            $person['full_name'] = trim($person['first_name'] . ' ' . $person['last_name']);
                        }
                        $emailsFound++;

                        Log::info('✅ PeopleSearchService: Hunter.io matched email via domain-search', [
                            'person_name' => $person['full_name'],
                            'email' => $person['email'],
                        ]);
                        break;
                    }
                }
            }
            unset($person);
        }

        // Step 3: For people still without email who have both first+last name, try email-finder
        foreach ($people as &$person) {
            if (!empty($person['email'])) {
                continue;
            }
            if (empty($person['first_name']) || empty($person['last_name'])) {
                continue;
            }

            try {
                $response = Http::timeout(15)->get('https://api.hunter.io/v2/email-finder', [
                    'api_key' => $hunterKey,
                    'domain' => $domain,
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $email = $result['data']['email'] ?? null;
                    $confidence = $result['data']['score'] ?? 0;

                    if (!empty($email)) {
                        $person['email'] = $email;
                        $person['email_verified'] = $confidence >= 70;
                        $person['data_source'] = 'apollo+hunter';
                        $emailsFound++;

                        Log::info('✅ PeopleSearchService: Hunter.io found email via email-finder', [
                            'person_name' => $person['full_name'],
                            'email' => $email,
                            'confidence' => $confidence,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ PeopleSearchService: Hunter.io email-finder exception', [
                    'person_name' => $person['full_name'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
        unset($person);

        Log::info('📧 PeopleSearchService: Hunter.io enrichment complete', [
            'company' => $company->name,
            'total_people' => count($people),
            'emails_found_by_hunter' => $emailsFound,
        ]);

        return $people;
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

<?php

namespace App\Services\Prospecting;

use App\Contracts\ProspectingProvider;
use App\Services\Prospecting\Concerns\MakesProspectingRequests;
use Illuminate\Support\Facades\Log;

class ProspeoProvider implements ProspectingProvider
{
    use MakesProspectingRequests;

    protected string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.prospeo.api_key');
    }

    public function getName(): string
    {
        return 'prospeo';
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function searchCompanies(array $criteria, int $limit = 10, ?string $referenceCompanyDomain = null): array
    {
        try {
            $filters = $this->buildCompanyFilters($criteria, $referenceCompanyDomain);

            if (empty($filters)) {
                return [
                    'success' => false,
                    'error' => 'No valid Prospeo search filters could be built from the criteria.',
                    'companies' => [],
                ];
            }

            $result = $this->executeCompanySearch($filters, $limit);

            if (!$result['success'] && ($result['error_code'] ?? '') === 'INVALID_FILTERS') {
                $result = $this->retryCompanySearchWithRelaxedFilters($criteria, $referenceCompanyDomain, $filters, $limit, $result);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('ProspeoProvider company search exception', [
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
     * @param  array<string, mixed>  $filters
     * @return array{success: bool, companies?: array<int, array<string, mixed>>, error?: string, error_code?: string, filter_error?: string, rate_limited?: bool}
     */
    protected function executeCompanySearch(array $filters, int $limit): array
    {
        $response = $this->postWithRetry(
            'https://api.prospeo.io/search-company',
            [
                'page' => 1,
                'filters' => $filters,
            ],
            $this->headers()
        );

        $data = $response->json();

        if (is_array($data) && !empty($data['error'])) {
            return [
                'success' => false,
                'error_code' => $data['error_code'] ?? 'UNKNOWN',
                'filter_error' => $data['filter_error'] ?? '',
                'error' => $this->formatApiError($data, 'company search'),
                'companies' => [],
            ];
        }

        if (!$response->successful()) {
            return $this->httpErrorResponse($response, 'company search');
        }

        $results = $data['results'] ?? [];
        $companies = array_map(
            fn (array $row) => $this->mapCompany($row['company'] ?? []),
            $results
        );

        $companies = array_slice($companies, 0, min($limit, 25));

        Log::info('ProspeoProvider: company search completed', [
            'count' => count($companies),
            'free' => $data['free'] ?? false,
        ]);

        return [
            'success' => true,
            'companies' => $companies,
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $previousResult
     * @return array{success: bool, companies?: array<int, array<string, mixed>>, error?: string, error_code?: string, filter_error?: string, rate_limited?: bool}
     */
    protected function retryCompanySearchWithRelaxedFilters(
        array $criteria,
        ?string $referenceCompanyDomain,
        array $filters,
        int $limit,
        array $previousResult
    ): array {
        $filterError = strtolower((string) ($previousResult['filter_error'] ?? ''));

        Log::channel('lead-jobs')->warning('ProspeoProvider: relaxing invalid company filters and retrying', [
            'filter_error' => $previousResult['filter_error'] ?? null,
        ]);

        if (str_contains($filterError, 'industry')) {
            unset($filters['company_industry']);
        }

        if (str_contains($filterError, 'location')) {
            unset($filters['company_location_search']);
        }

        if (str_contains($filterError, 'employee')) {
            unset($filters['company_employee_count']);
        }

        if (str_contains($filterError, 'keyword')) {
            unset($filters['company_keywords']);
        }

        if (empty($filters['company_industry']) && empty($filters['company_keywords'])) {
            $keywordFallback = array_values(array_filter(array_unique(array_merge(
                $criteria['keywords'] ?? [],
                $criteria['industries'] ?? [],
                !empty($criteria['industry']) ? [$criteria['industry']] : []
            ))));

            if (!empty($keywordFallback)) {
                $filters['company_keywords'] = [
                    'include' => array_slice($keywordFallback, 0, 5),
                ];
            }
        }

        if ($referenceCompanyDomain && !empty($filters) && empty($filters['company'] ?? null)) {
            $domain = preg_replace('#^https?://#', '', $referenceCompanyDomain);
            $domain = preg_replace('#^www\.#', '', (string) $domain);
            $filters['company'] = [
                'websites' => [
                    'exclude' => [rtrim($domain, '/')],
                ],
            ];
        }

        if (empty($filters)) {
            return $previousResult;
        }

        return $this->executeCompanySearch($filters, $limit);
    }

    public function searchPeople(array $companyContext, array $jobTitles, int $limit = 10): array
    {
        try {
            $perTitleLimit = max(1, min($limit, 1));
            $titlesToSearch = empty($jobTitles) ? [''] : $jobTitles;

            foreach ($titlesToSearch as $title) {
                $filters = $this->buildPersonFilters($companyContext, $title);

                $response = $this->postWithRetry(
                    'https://api.prospeo.io/search-person',
                    [
                        'page' => 1,
                        'filters' => $filters,
                    ],
                    $this->headers()
                );

                if (!$response->successful()) {
                    if ($response->status() === 429) {
                        return [
                            'success' => false,
                            'rate_limited' => true,
                            'error' => 'Prospeo rate limit exceeded.',
                            'people' => [],
                        ];
                    }

                    return $this->httpErrorResponse($response, 'person search');
                }

                $data = $response->json();

                if (!empty($data['error'])) {
                    if (($data['error_code'] ?? '') === 'NO_RESULTS') {
                        continue;
                    }

                    return $this->errorResponse($data, 'person search');
                }

                $results = array_slice($data['results'] ?? [], 0, $perTitleLimit);

                foreach ($results as $row) {
                    $personData = $row['person'] ?? [];
                    $personId = (string) ($personData['person_id'] ?? '');

                    if ($personId === '') {
                        continue;
                    }

                    $mapped = $this->mapPerson($personData, $companyContext, $row['company'] ?? []);

                    $enriched = $this->enrichPerson($personId, [
                        'company_context' => $companyContext,
                        'person_data' => $personData,
                        'company_data' => $row['company'] ?? [],
                    ]);

                    if ($enriched['success'] && !empty($enriched['person'])) {
                        $mapped = array_merge($mapped, $enriched['person']);
                    }

                    if (!empty(trim((string) ($mapped['email'] ?? '')))) {
                        return [
                            'success' => true,
                            'people' => [$mapped],
                        ];
                    }
                }
            }

            return [
                'success' => true,
                'people' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('ProspeoProvider people search exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'people' => [],
            ];
        }
    }

    public function enrichPerson(string $personId, array $context = []): array
    {
        try {
            $response = $this->postWithRetry(
                'https://api.prospeo.io/enrich-person',
                [
                    'only_verified_email' => true,
                    'data' => [
                        'person_id' => $personId,
                    ],
                ],
                $this->headers(),
                15
            );

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => "Prospeo enrich-person HTTP {$response->status()}.",
                ];
            }

            $data = $response->json();

            if (!empty($data['error'])) {
                $errorCode = $data['error_code'] ?? 'UNKNOWN';

                if ($errorCode === 'NO_MATCH') {
                    return [
                        'success' => false,
                        'error' => 'No verified email found for this person.',
                    ];
                }

                return [
                    'success' => false,
                    'error' => "Prospeo enrich-person error: {$errorCode}",
                ];
            }

            $person = $data['person'] ?? [];
            $emailBlock = $person['email'] ?? [];
            $email = is_array($emailBlock) ? ($emailBlock['email'] ?? null) : null;
            $emailStatus = is_array($emailBlock) ? ($emailBlock['status'] ?? null) : null;
            $emailRevealed = is_array($emailBlock) ? ($emailBlock['revealed'] ?? false) : false;

            if (empty($email) || !$emailRevealed) {
                return [
                    'success' => false,
                    'error' => 'Prospeo did not return a revealed verified email.',
                ];
            }

            $mobileBlock = $person['mobile'] ?? [];
            $phone = is_array($mobileBlock) && ($mobileBlock['revealed'] ?? false)
                ? ($mobileBlock['mobile'] ?? null)
                : null;

            Log::info('ProspeoProvider: person enriched', [
                'person_id' => $personId,
                'email_status' => $emailStatus,
                'free_enrichment' => $data['free_enrichment'] ?? false,
            ]);

            return [
                'success' => true,
                'person' => [
                    'email' => $email,
                    'phone' => $phone,
                    'email_verified' => ($emailStatus ?? '') === 'VERIFIED',
                    'first_name' => $person['first_name'] ?? ($context['person_data']['first_name'] ?? null),
                    'last_name' => $person['last_name'] ?? ($context['person_data']['last_name'] ?? null),
                    'full_name' => $person['full_name'] ?? null,
                    'title' => $person['current_job_title'] ?? null,
                    'linkedin_url' => $person['linkedin_url'] ?? null,
                    'metadata' => $data,
                ],
            ];
        } catch (\Throwable $e) {
            Log::warning('ProspeoProvider enrich-person exception', [
                'person_id' => $personId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCompanyFilters(array $criteria, ?string $referenceCompanyDomain): array
    {
        $filters = [];

        $industryTerms = [];
        if (!empty($criteria['industries']) && is_array($criteria['industries'])) {
            $industryTerms = array_slice(array_filter($criteria['industries']), 0, 5);
        }
        if (!empty($criteria['industry']) && !in_array($criteria['industry'], $industryTerms, true)) {
            array_unshift($industryTerms, $criteria['industry']);
        }

        $resolvedIndustries = $this->resolveIndustries($industryTerms);
        if (!empty($resolvedIndustries)) {
            $filters['company_industry'] = ['include' => $resolvedIndustries];
        }

        $locationTerms = [];
        if (!empty($criteria['countries']) && is_array($criteria['countries'])) {
            foreach (array_slice($criteria['countries'], 0, 5) as $country) {
                if (!empty($country)) {
                    $locationTerms[] = $country;
                }
            }
        } elseif (!empty($criteria['country'])) {
            $locationTerms[] = $criteria['country'];
        }

        $resolvedLocations = $this->resolveLocations($locationTerms);
        if (!empty($resolvedLocations)) {
            $filters['company_location_search'] = ['include' => $resolvedLocations];
        }

        if (!empty($criteria['company_size_min']) || !empty($criteria['company_size_max'])) {
            $filters['company_employee_count'] = array_filter([
                'min' => $criteria['company_size_min'] ?? null,
                'max' => $criteria['company_size_max'] ?? null,
            ], fn ($value) => $value !== null);
        }

        if (!empty($criteria['keywords']) && is_array($criteria['keywords'])) {
            $keywords = array_slice(array_filter(array_unique($criteria['keywords'])), 0, 5);
            if (!empty($keywords)) {
                $filters['company_keywords'] = ['include' => $keywords];
            }
        }

        if (empty($filters) && !empty($industryTerms)) {
            $filters['company_keywords'] = [
                'include' => array_slice($industryTerms, 0, 5),
            ];
        }

        if ($referenceCompanyDomain && !empty($filters)) {
            $domain = preg_replace('#^https?://#', '', $referenceCompanyDomain);
            $domain = preg_replace('#^www\.#', '', (string) $domain);
            $filters['company'] = [
                'websites' => [
                    'exclude' => [rtrim($domain, '/')],
                ],
            ];
        }

        return $filters;
    }

    /**
     * @param  array{name: string, domain: ?string, external_id: ?string, data_source: ?string}  $companyContext
     * @return array<string, mixed>
     */
    protected function buildPersonFilters(array $companyContext, string $title): array
    {
        $filters = [];

        if ($title !== '') {
            $filters['person_job_title'] = [
                'include' => [$title],
                'match_mode' => 'CONTAINS',
            ];
        }

        if (!empty($companyContext['external_id']) && ($companyContext['data_source'] ?? '') === 'prospeo') {
            $filters['company'] = [
                'company_oids' => [
                    'include' => [$companyContext['external_id']],
                ],
            ];
        } elseif (!empty($companyContext['domain'])) {
            $domain = preg_replace('#^https?://#', '', $companyContext['domain']);
            $domain = preg_replace('#^www\.#', '', $domain);
            $filters['company'] = [
                'websites' => [
                    'include' => [rtrim($domain, '/')],
                ],
            ];
        } else {
            $filters['company'] = [
                'names' => [
                    'include' => [$companyContext['name']],
                ],
            ];
        }

        return $filters;
    }

    /**
     * @param  list<string>  $terms
     * @return list<string>
     */
    protected function resolveIndustries(array $terms): array
    {
        $resolved = [];

        foreach (array_slice($terms, 0, 3) as $term) {
            $suggestions = $this->fetchSuggestions('industry_search', $term);

            if (!empty($suggestions)) {
                $resolved[] = $suggestions[0];
            }
        }

        return array_values(array_unique($resolved));
    }

    /**
     * @param  list<string>  $terms
     * @return list<string>
     */
    protected function resolveLocations(array $terms): array
    {
        $resolved = [];

        foreach (array_slice($terms, 0, 3) as $term) {
            $query = $this->normalizeLocationQuery($term);
            $suggestions = $this->fetchSuggestions('location_search', $query);

            foreach ($suggestions as $suggestion) {
                if (is_array($suggestion)) {
                    if (($suggestion['type'] ?? null) === 'COUNTRY' && !empty($suggestion['name'])) {
                        $resolved[] = $suggestion['name'];
                        break;
                    }

                    continue;
                }

                if (is_string($suggestion) && $suggestion !== '') {
                    $resolved[] = $suggestion;
                    break;
                }
            }
        }

        return array_values(array_unique($resolved));
    }

    protected function normalizeLocationQuery(string $term): string
    {
        $term = trim($term);

        return match (strtoupper($term)) {
            'US', 'USA', 'U.S.', 'U.S.A.' => 'United States',
            'UK', 'U.K.' => 'United Kingdom',
            default => $term,
        };
    }

    /**
     * @return list<string|array<string, mixed>>
     */
    protected function fetchSuggestions(string $parameter, string $query): array
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return [];
        }

        try {
            $response = $this->postWithRetry(
                'https://api.prospeo.io/search-suggestions',
                [$parameter => $query],
                $this->headers(),
                15
            );

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();

            if (!is_array($data) || !empty($data['error'])) {
                return [];
            }

            return match ($parameter) {
                'industry_search' => $data['industry_suggestions'] ?? [],
                'location_search' => $data['location_suggestions'] ?? [],
                'job_title_search' => $data['job_title_suggestions'] ?? [],
                default => [],
            };
        } catch (\Throwable $e) {
            Log::warning('ProspeoProvider: suggestion lookup failed', [
                'parameter' => $parameter,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function formatApiError(array $data, string $operation): string
    {
        $errorCode = $data['error_code'] ?? 'UNKNOWN';
        $filterError = $data['filter_error'] ?? '';
        $message = "Prospeo {$operation} error: {$errorCode}";

        if ($filterError) {
            $message .= " — {$filterError}";
        }

        return $message;
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [
            'X-KEY' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    protected function mapCompany(array $data): array
    {
        $website = $data['website'] ?? null;
        $domain = $data['domain'] ?? null;

        if (!$domain && $website) {
            $domain = parse_url($website, PHP_URL_HOST) ?? $website;
            $domain = str_replace(['http://', 'https://', 'www.'], '', $domain);
        }

        $location = $data['location'] ?? [];

        return [
            'name' => $data['name'] ?? '',
            'domain' => $domain,
            'website' => $website,
            'industry' => $data['industry'] ?? null,
            'country' => is_array($location) ? ($location['country'] ?? null) : null,
            'city' => is_array($location) ? ($location['city'] ?? null) : null,
            'state' => is_array($location) ? ($location['state'] ?? null) : null,
            'description' => $data['description'] ?? $data['description_ai'] ?? null,
            'employee_count' => $data['employee_count'] ?? null,
            'revenue' => $this->normalizeRevenue($data),
            'founded_year' => $data['founded'] ?? null,
            'technologies' => $data['technology']['technology_names'] ?? [],
            'metadata' => $data,
            'data_source' => 'prospeo',
            'external_id' => (string) ($data['company_id'] ?? null),
        ];
    }

    protected function normalizeRevenue(array $data): ?float
    {
        $range = $data['revenue_range'] ?? null;

        if (is_array($range) && isset($range['min']) && is_numeric($range['min'])) {
            return (float) $range['min'];
        }

        if (is_array($range) && isset($range['max']) && is_numeric($range['max'])) {
            return (float) $range['max'];
        }

        return null;
    }

    /**
     * @param  array{name: string, domain: ?string, external_id: ?string, data_source: ?string, id?: int}  $companyContext
     */
    protected function mapPerson(array $personData, array $companyContext, array $companyData = []): array
    {
        return [
            'company_id' => $companyContext['id'] ?? null,
            'first_name' => $personData['first_name'] ?? null,
            'last_name' => $personData['last_name'] ?? null,
            'full_name' => $personData['full_name'] ?? trim(($personData['first_name'] ?? '').' '.($personData['last_name'] ?? '')),
            'title' => $personData['current_job_title'] ?? null,
            'email' => null,
            'phone' => null,
            'linkedin_url' => $personData['linkedin_url'] ?? null,
            'twitter_handle' => null,
            'bio' => $personData['headline'] ?? null,
            'metadata' => [
                'person' => $personData,
                'company' => $companyData,
            ],
            'data_source' => 'prospeo',
            'external_id' => (string) ($personData['person_id'] ?? null),
            'email_verified' => false,
        ];
    }

    /**
     * @return array{success: false, error: string, companies?: array, people?: array, rate_limited?: bool}
     */
    protected function errorResponse(array $data, string $operation): array
    {
        $message = $this->formatApiError($data, $operation);

        Log::channel('lead-jobs')->error($message, $data);

        return [
            'success' => false,
            'error' => $message,
            'error_code' => $data['error_code'] ?? 'UNKNOWN',
            'filter_error' => $data['filter_error'] ?? '',
            'companies' => [],
            'people' => [],
        ];
    }

    /**
     * @return array{success: false, error: string, companies?: array, people?: array, rate_limited?: bool}
     */
    protected function httpErrorResponse($response, string $operation): array
    {
        $body = substr($response->body(), 0, 400);
        $data = json_decode($response->body(), true);

        if (is_array($data) && !empty($data['error_code'])) {
            return [
                'success' => false,
                'error' => $this->formatApiError($data, $operation),
                'error_code' => $data['error_code'],
                'filter_error' => $data['filter_error'] ?? '',
                'companies' => [],
                'people' => [],
                'rate_limited' => $response->status() === 429,
            ];
        }

        $hint = match ($response->status()) {
            401 => 'Invalid Prospeo API key.',
            429 => 'Prospeo rate limit hit after retries.',
            default => 'Check Prospeo account status and request payload.',
        };

        Log::channel('lead-jobs')->error("Prospeo {$operation} HTTP error", [
            'status' => $response->status(),
            'body' => $body,
            'hint' => $hint,
        ]);

        return [
            'success' => false,
            'error' => "Prospeo HTTP {$response->status()}: {$hint} Response: {$body}",
            'companies' => [],
            'people' => [],
            'rate_limited' => $response->status() === 429,
        ];
    }
}

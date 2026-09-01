<?php

namespace App\Services\Prospecting;

use App\Contracts\ProspectingProvider;
use App\Services\Prospecting\Concerns\MakesProspectingRequests;
use Illuminate\Support\Facades\Log;

class ApolloProvider implements ProspectingProvider
{
    use MakesProspectingRequests;

    protected string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.apollo.api_key');
    }

    public function getName(): string
    {
        return 'apollo';
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function searchCompanies(array $criteria, int $limit = 10, ?string $referenceCompanyDomain = null): array
    {
        try {
            $body = [
                'page' => 1,
                'per_page' => $limit,
            ];

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

            if (!empty($criteria['company_size_min']) || !empty($criteria['company_size_max'])) {
                $min = $criteria['company_size_min'] ?? 1;
                $max = $criteria['company_size_max'] ?? 10000;
                $body['organization_num_employees_ranges'] = ["{$min},{$max}"];
            }

            if (!empty($criteria['keywords']) && is_array($criteria['keywords'])) {
                $keywords = array_slice(array_filter(array_unique($criteria['keywords'])), 0, 5);
                if (!empty($keywords)) {
                    $body['q_organization_keyword_tags'] = array_values(array_unique(array_merge(
                        $body['q_organization_keyword_tags'] ?? [],
                        $keywords
                    )));
                }
            }

            $response = $this->postWithRetry(
                'https://api.apollo.io/api/v1/mixed_companies/search',
                $body,
                $this->headers()
            );

            if ($response->successful()) {
                $data = $response->json();
                $companies = $data['organizations'] ?? $data['accounts'] ?? [];

                return [
                    'success' => true,
                    'companies' => array_map([$this, 'mapCompany'], $companies),
                ];
            }

            $apolloBody = substr($response->body(), 0, 400);
            $apolloHint = match ($response->status()) {
                401 => 'Apollo rejected the API key (invalid or missing).',
                403 => 'Apollo forbade this endpoint (plan/permission).',
                429 => 'Apollo rate limit hit after retries.',
                default => 'Check Apollo account status and API key permissions.',
            };

            Log::channel('lead-jobs')->error('Apollo company search failed', [
                'status' => $response->status(),
                'body' => $apolloBody,
                'hint' => $apolloHint,
            ]);

            return [
                'success' => false,
                'error' => "Apollo HTTP {$response->status()}: {$apolloHint} Response: {$apolloBody}",
                'companies' => [],
                'rate_limited' => $response->status() === 429,
            ];
        } catch (\Throwable $e) {
            Log::error('ApolloProvider company search exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'companies' => [],
            ];
        }
    }

    public function searchPeople(array $companyContext, array $jobTitles, int $limit = 10): array
    {
        try {
            $people = [];
            $perTitleLimit = max(1, min($limit, 1));
            $titlesToSearch = empty($jobTitles) ? [''] : $jobTitles;

            foreach ($titlesToSearch as $title) {
                $query = [
                    'page' => 1,
                    'per_page' => $perTitleLimit,
                ];

                if ($title !== '') {
                    $query['person_titles'] = [$title];
                }

                if (!empty($companyContext['external_id']) && ($companyContext['data_source'] ?? '') === 'apollo') {
                    $query['organization_ids'] = [$companyContext['external_id']];
                } elseif (!empty($companyContext['domain'])) {
                    $query['q_organization_domains'] = $companyContext['domain'];
                } else {
                    $query['q_organization_name'] = $companyContext['name'];
                }

                $response = $this->postWithRetry(
                    'https://api.apollo.io/api/v1/mixed_people/api_search',
                    $query,
                    $this->headers()
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $found = $data['people'] ?? [];

                    foreach ($found as $personData) {
                        if (empty($personData['email']) && !empty($personData['id'])) {
                            $enriched = $this->enrichPerson((string) $personData['id'], [
                                'person_data' => $personData,
                            ]);
                            if ($enriched['success'] && !empty($enriched['person'])) {
                                $personData = array_merge($personData, $enriched['person']);
                            }
                        }

                        $people[] = $this->mapPerson($personData, $companyContext);
                    }

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

                    if ($response->status() === 403 && $errorCode === 'API_INACCESSIBLE') {
                        return [
                            'success' => false,
                            'apollo_plan_limited' => true,
                            'error' => 'Apollo people search requires a paid plan.',
                            'people' => [],
                        ];
                    }

                    if ($response->status() === 429) {
                        return [
                            'success' => false,
                            'rate_limited' => true,
                            'error' => 'Apollo rate limit exceeded.',
                            'people' => [],
                        ];
                    }
                }
            }

            $peopleWithEmail = array_values(array_filter(
                $people,
                fn (array $person): bool => !empty(trim((string) ($person['email'] ?? '')))
            ));

            return [
                'success' => true,
                'people' => array_slice($peopleWithEmail, 0, 1),
            ];
        } catch (\Throwable $e) {
            Log::error('ApolloProvider people search exception', [
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
        $personData = $context['person_data'] ?? [];

        try {
            $matchBody = [
                'id' => $personId,
                'reveal_personal_emails' => true,
            ];

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

            $response = $this->postWithRetry(
                'https://api.apollo.io/api/v1/people/match',
                $matchBody,
                $this->headers(),
                15
            );

            if ($response->successful()) {
                $result = $response->json();
                $person = $result['person'] ?? $result;
                $email = $person['email'] ?? $person['contact_email'] ?? null;
                $phone = $person['phone_numbers'][0]['number'] ?? $person['phone_numbers'][0]['sanitized_number'] ?? null;

                if (!empty($email) || !empty($phone)) {
                    return [
                        'success' => true,
                        'person' => [
                            'email' => $email,
                            'phone' => $phone,
                            'phone_numbers' => $person['phone_numbers'] ?? $personData['phone_numbers'] ?? [],
                            'email_verified' => !empty($email),
                        ],
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ApolloProvider email reveal exception', [
                'person_id' => $personId,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'success' => false,
            'error' => 'Could not reveal email from Apollo.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [
            'X-Api-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache',
        ];
    }

    protected function mapCompany(array $data): array
    {
        $websiteUrl = $data['website_url'] ?? $data['primary_domain'] ?? null;
        $domain = null;
        if ($websiteUrl) {
            $domain = parse_url($websiteUrl, PHP_URL_HOST) ?? $websiteUrl;
            $domain = str_replace(['http://', 'https://', 'www.'], '', $domain);
        }

        return [
            'name' => $data['name'] ?? '',
            'domain' => $domain,
            'website' => $websiteUrl ? (str_starts_with($websiteUrl, 'http') ? $websiteUrl : 'https://'.$websiteUrl) : null,
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
     * @param  array{name: string, domain: ?string, external_id: ?string, data_source: ?string, id?: int}  $companyContext
     */
    protected function mapPerson(array $data, array $companyContext): array
    {
        $email = $data['email']
            ?? $data['contact_email']
            ?? $data['personal_emails'][0] ?? null;

        return [
            'company_id' => $companyContext['id'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'full_name' => trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
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
}

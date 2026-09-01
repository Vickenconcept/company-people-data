<?php

namespace App\Services;

use App\Contracts\ProspectingProvider;
use App\Models\Company;
use App\Models\Person;
use App\Models\User;
use App\Services\Prospecting\ProspectingProviderFactory;
use App\Support\JobTitleSearch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PeopleSearchService
{
    protected ProspectingProvider $provider;

    protected string $service;

    protected ?User $user = null;

    public function __construct(?ProspectingProvider $provider = null)
    {
        $this->provider = $provider ?? ProspectingProviderFactory::make();
        $this->service = $this->provider->getName();
    }

    public function setApiKeyFromUser(User $user, ?string $service = null): self
    {
        $this->user = $user;

        if ($service !== null && $service !== $this->provider->getName()) {
            if ($service === 'hunter') {
                $this->service = 'hunter';

                return $this;
            }

            $this->provider = ProspectingProviderFactory::make($service);
            $this->service = $this->provider->getName();
        }

        $apiKey = $user->apiKeys()
            ->where('service', $this->provider->getName())
            ->where('is_active', true)
            ->first();

        if ($apiKey) {
            $this->provider->setApiKey($apiKey->api_key);
        }

        return $this;
    }

    /**
     * Find people by company and job titles.
     * Falls back to Hunter.io when the primary provider fails.
     */
    public function findPeople(Company $company, array $jobTitles, int $limit = 10): array
    {
        $jobTitles = JobTitleSearch::resolveTitles($jobTitles);

        if ($this->service === 'hunter') {
            return $this->findWithHunter($company, $jobTitles, $limit);
        }

        $result = $this->findWithProvider($company, $jobTitles, $limit);

        $shouldFallback = !$result['success']
            || (!empty($result['apollo_plan_limited']))
            || (empty($result['people']) && ($result['success'] ?? false));

        if ($shouldFallback) {
            Log::info('PeopleSearchService: Primary provider unavailable, trying Hunter.io', [
                'provider' => $this->provider->getName(),
                'company_id' => $company->id,
                'reason' => $result['error'] ?? 'No contacts found',
            ]);

            $hunterResult = $this->tryHunterFallback($company, $jobTitles, $limit);
            if ($hunterResult['success'] && !empty($hunterResult['people'])) {
                return $hunterResult;
            }
        }

        if ($result['success'] && !empty($result['people'])) {
            $providerName = $this->provider->getName();
            $people = $this->enrichEmailsWithHunter($result['people'], $company, $providerName);

            $peopleWithEmail = array_values(array_filter(
                $people,
                fn (array $person): bool => !empty(trim((string) ($person['email'] ?? '')))
            ));

            return [
                'success' => true,
                'people' => array_slice($peopleWithEmail, 0, 1),
            ];
        }

        return $result;
    }

    protected function findWithProvider(Company $company, array $jobTitles, int $limit): array
    {
        try {
            $companyContext = [
                'id' => $company->id,
                'name' => $company->name,
                'domain' => $company->domain,
                'external_id' => $company->external_id,
                'data_source' => $company->data_source,
            ];

            $result = $this->provider->searchPeople($companyContext, $jobTitles, $limit);

            if (!empty($result['people'])) {
                foreach ($result['people'] as &$person) {
                    $person['company_id'] = $company->id;
                }
                unset($person);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('PeopleSearchService: Provider exception', [
                'provider' => $this->provider->getName(),
                'company_id' => $company->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'people' => [],
            ];
        }
    }

    protected function tryHunterFallback(Company $company, array $jobTitles, int $limit): array
    {
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
            return [
                'success' => false,
                'error' => 'Hunter.io API key not configured.',
                'people' => [],
            ];
        }

        $previousService = $this->service;
        $this->service = 'hunter';

        $result = $this->findWithHunter($company, $jobTitles, $limit, $hunterKey);

        $this->service = $previousService;

        return $result;
    }

    protected function findWithHunter(Company $company, array $jobTitles, int $limit, ?string $hunterKey = null): array
    {
        try {
            $people = [];
            $perTitleLimit = max(1, min($limit, 1));
            $apiKey = $hunterKey ?? config('services.hunter.api_key');
            $titlesToSearch = empty($jobTitles) ? [''] : $jobTitles;

            $domain = $company->domain;
            if (str_starts_with((string) $domain, 'www.')) {
                $domain = substr($domain, 4);
            }

            foreach ($titlesToSearch as $title) {
                $seniority = $title !== '' ? $this->mapTitleToSeniority($title) : null;

                $queryParams = [
                    'api_key' => $apiKey,
                    'domain' => $domain,
                    'limit' => $perTitleLimit,
                ];

                if ($seniority && $seniority !== 'employee') {
                    $queryParams['seniority'] = $seniority;
                }

                $response = Http::timeout(30)->get('https://api.hunter.io/v2/domain-search', $queryParams);

                if ($response->successful()) {
                    $data = $response->json();
                    $found = $data['data']['emails'] ?? $data['emails'] ?? [];

                    foreach ($found as $emailData) {
                        $personTitle = strtolower($emailData['position'] ?? $emailData['title'] ?? '');
                        $searchTitle = strtolower($title);

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
                }
            }

            return [
                'success' => true,
                'people' => array_slice($people, 0, 1),
            ];
        } catch (\Exception $e) {
            Log::error('PeopleSearchService: Hunter.io exception', [
                'company_id' => $company->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'people' => [],
            ];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $people
     * @return array<int, array<string, mixed>>
     */
    protected function enrichEmailsWithHunter(array $people, Company $company, string $primaryProvider): array
    {
        $needsEmail = false;
        foreach ($people as $person) {
            if (empty($person['email'])) {
                $needsEmail = true;
                break;
            }
        }

        if (!$needsEmail) {
            return $people;
        }

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

        $domain = $company->domain;
        if (empty($domain)) {
            return $people;
        }
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = rtrim($domain, '/');

        $domainEmails = [];
        try {
            $response = Http::timeout(15)->get('https://api.hunter.io/v2/domain-search', [
                'api_key' => $hunterKey,
                'domain' => $domain,
                'limit' => 50,
            ]);

            if ($response->successful()) {
                $domainEmails = $response->json('data.emails') ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('PeopleSearchService: Hunter domain-search exception', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
        }

        $combinedSource = "{$primaryProvider}+hunter";

        foreach ($people as &$person) {
            if (!empty($person['email']) || empty($person['first_name'])) {
                continue;
            }

            $firstName = strtolower(trim($person['first_name']));
            foreach ($domainEmails as $hunterEmail) {
                $hunterFirst = strtolower(trim($hunterEmail['first_name'] ?? ''));
                $hunterLast = strtolower(trim($hunterEmail['last_name'] ?? ''));
                $personLast = strtolower(trim($person['last_name'] ?? ''));

                $firstMatch = !empty($hunterFirst) && $hunterFirst === $firstName;
                $lastMatch = empty($personLast) || empty($hunterLast) || $hunterLast === $personLast;

                if ($firstMatch && $lastMatch && !empty($hunterEmail['value'])) {
                    $person['email'] = $hunterEmail['value'];
                    $person['email_verified'] = ($hunterEmail['verification']['status'] ?? '') === 'valid';
                    $person['data_source'] = $combinedSource;
                    break;
                }
            }
        }
        unset($person);

        foreach ($people as &$person) {
            if (!empty($person['email']) || empty($person['first_name']) || empty($person['last_name'])) {
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
                    $email = $response->json('data.email');
                    $confidence = $response->json('data.score') ?? 0;

                    if (!empty($email)) {
                        $person['email'] = $email;
                        $person['email_verified'] = $confidence >= 70;
                        $person['data_source'] = $combinedSource;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('PeopleSearchService: Hunter email-finder exception', [
                    'person_name' => $person['full_name'] ?? '',
                    'error' => $e->getMessage(),
                ]);
            }
        }
        unset($person);

        return $people;
    }

    protected function mapHunterPerson(array $data, int $companyId): array
    {
        return [
            'company_id' => $companyId,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'full_name' => ($data['first_name'] ?? '').' '.($data['last_name'] ?? ''),
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

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $people
     * @return array<int, Person>
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

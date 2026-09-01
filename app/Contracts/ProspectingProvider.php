<?php

namespace App\Contracts;

interface ProspectingProvider
{
    public function getName(): string;

    public function setApiKey(string $apiKey): self;
    

    /**
     * Search for companies matching ICP criteria.
     *
     * @return array{success: bool, companies: array<int, array<string, mixed>>, error?: string, warning?: string}
     */
    public function searchCompanies(array $criteria, int $limit = 10, ?string $referenceCompanyDomain = null): array;


    /**
     * Search for people at a company by job titles.
     *
     * @param  array{name: string, domain: ?string, external_id: ?string, data_source: ?string}  $companyContext
     * @return array{success: bool, people: array<int, array<string, mixed>>, error?: string, rate_limited?: bool}
     */
    public function searchPeople(array $companyContext, array $jobTitles, int $limit = 10): array;

    /**
     * Reveal verified contact data for a person (email enrichment).
     *
     * @param  array<string, mixed>  $context
     * @return array{success: bool, person?: array<string, mixed>, error?: string}
     */
    public function enrichPerson(string $personId, array $context = []): array;
}

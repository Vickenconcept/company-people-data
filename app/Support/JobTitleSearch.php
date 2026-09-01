<?php

namespace App\Support;

class JobTitleSearch
{
    public const ANYBODY = 'Anybody';

    /** @var list<string> */
    private const ANYBODY_ALIASES = [
        'anybody',
        'anyone',
        'any person',
        'any contact',
        '*',
    ];

    public static function isAnybodySearch(array $jobTitles): bool
    {
        foreach ($jobTitles as $title) {
            if (in_array(strtolower(trim((string) $title)), self::ANYBODY_ALIASES, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function resolveTitles(array $jobTitles): array
    {
        if (self::isAnybodySearch($jobTitles)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($title) => trim((string) $title),
            $jobTitles
        )));
    }

    public static function displayLabel(array $jobTitles): string
    {
        if (self::isAnybodySearch($jobTitles)) {
            return self::ANYBODY;
        }

        return implode(', ', self::resolveTitles($jobTitles));
    }
}

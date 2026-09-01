<?php

namespace App\Services\Prospecting;

use App\Contracts\ProspectingProvider;
use InvalidArgumentException;

class ProspectingProviderFactory
{
    public static function make(?string $provider = null): ProspectingProvider
    {
        $provider = $provider ?? config('services.prospecting.provider', 'prospeo');

        return match ($provider) {
            'apollo' => new ApolloProvider(config('services.apollo.api_key')),
            'prospeo' => new ProspeoProvider(config('services.prospeo.api_key')),
            default => throw new InvalidArgumentException("Unknown prospecting provider: {$provider}"),
        };
    }
}

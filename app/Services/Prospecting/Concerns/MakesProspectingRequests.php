<?php

namespace App\Services\Prospecting\Concerns;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait MakesProspectingRequests
{
    protected int $maxRetries = 5;

    /** @var list<int> */
    protected array $retryDelaysSeconds = [1, 2, 4, 8, 16];

    /**
     * @param  array<string, string>  $headers
     */
    protected function postWithRetry(string $url, array $body, array $headers, int $timeout = 30): Response
    {
        $attempt = 0;
        $response = null;

        while ($attempt <= $this->maxRetries) {
            $response = Http::timeout($timeout)
                ->withHeaders($headers)
                ->post($url, $body);

            if (!$this->shouldRetryResponse($response)) {
                return $response;
            }

            $delay = $this->retryDelaysSeconds[$attempt] ?? end($this->retryDelaysSeconds);
            Log::channel('lead-jobs')->warning('Prospecting API rate limited, retrying', [
                'provider' => method_exists($this, 'getName') ? $this->getName() : 'unknown',
                'url' => $url,
                'attempt' => $attempt + 1,
                'delay_seconds' => $delay,
                'status' => $response->status(),
            ]);

            sleep($delay);
            $attempt++;
        }

        return $response;
    }

    protected function shouldRetryResponse(Response $response): bool
    {
        if ($response->status() === 429) {
            return true;
        }

        if ($response->status() === 400) {
            $errorCode = $response->json('error_code');

            return in_array($errorCode, [
                'SERVICE_TEMPORARILY_UNAVAILABLE',
                'Rate limit exceeded',
            ], true);
        }

        return false;
    }
}

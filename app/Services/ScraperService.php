<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScraperService
{
    protected string $apiKey;
    protected string $provider;

    public function __construct(?string $apiKey = null, ?string $provider = null)
    {
        $this->provider = $provider ?? config('services.scraper.provider', 'scraperapi');
        $this->apiKey = $apiKey ?? $this->getDefaultApiKeyForProvider();
    }

    /**
     * Set API key from user's stored keys
     */
    public function setApiKeyFromUser(User $user): self
    {
        $apiKey = $user->apiKeys()
            ->where('service', 'scraperapi')
            ->where('is_active', true)
            ->first();

        if ($apiKey) {
            $this->apiKey = $apiKey->api_key;
        }

        return $this;
    }

    /**
     * Scrape website content using ScraperAPI
     * or ScrapingBee depending on configured provider.
     * For ScraperAPI, automatically retries with premium modes for protected sites.
     */
    public function scrapeWebsite(string $url): array
    {
        if ($this->provider === 'scrapingbee') {
            return $this->scrapeWithScrapingBee($url);
        }

        // Try standard scrape first
        $result = $this->attemptScrape($url, false);
        
        // If it fails with 500 and mentions protected domains, try premium mode
        if (!$result['success'] && 
            $result['status'] === 500 && 
            (str_contains($result['error'] ?? '', 'Protected domains') || 
             str_contains($result['error'] ?? '', 'premium') ||
             str_contains($result['error'] ?? '', 'Cloudflare'))) {
            
            // Try with premium mode
            $premiumResult = $this->attemptScrape($url, true);
            
            // If premium also fails, try ultra_premium
            if (!$premiumResult['success'] && 
                $premiumResult['status'] === 500) {
                
                return $this->attemptScrape($url, true, true);
            }
            
            return $premiumResult;
        }
        
        return $result;
    }
    
    /**
     * Attempt to scrape with optional premium/ultra_premium modes
     */
    protected function attemptScrape(string $url, bool $premium = false, bool $ultraPremium = false): array
    {
        try {
            $params = [
                'api_key' => $this->apiKey,
                'url' => $url,
                'render' => 'true', // Render JavaScript
            ];
            
            if ($ultraPremium) {
                $params['ultra_premium'] = 'true';
            } elseif ($premium) {
                $params['premium'] = 'true';
            }
            
            // Increase timeout to 90 seconds for premium modes (they take longer)
            $timeout = ($premium || $ultraPremium) ? 90 : 60;
            
            $response = Http::timeout($timeout)->get('http://api.scraperapi.com', $params);

            if ($response->successful()) {
                $html = $response->body();
                $content = $this->extractTextContent($html);

                return [
                    'success' => true,
                    'content' => $content,
                    'html' => $html,
                    'url' => $url,
                ];
            }

            $errorBody = $response->body();
            Log::error('❌ ScraperService: API Error', [
                'url' => $url,
                'status' => $response->status(),
                'body' => substr($errorBody, 0, 500),
                'premium_mode' => $premium || $ultraPremium,
            ]);

            return [
                'success' => false,
                'status' => $response->status(),
                'error' => 'Failed to scrape website: HTTP ' . $response->status() . ' - ' . substr($errorBody, 0, 200),
            ];
        } catch (\Exception $e) {
            $isTimeout = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'timeout');
            
            Log::error('❌ ScraperService: Exception', [
                'url' => $url,
                'message' => $e->getMessage(),
                'is_timeout' => $isTimeout,
                'premium_mode' => $premium || $ultraPremium,
            ]);

            return [
                'success' => false,
                'status' => $isTimeout ? 408 : 500,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get default API key based on configured provider
     */
    protected function getDefaultApiKeyForProvider(): ?string
    {
        return match ($this->provider) {
            'scrapingbee' => config('services.scrapingbee.api_key'),
            default => config('services.scraperapi.api_key'),
        };
    }

    /**
     * Scrape using ScrapingBee API
     */
    protected function scrapeWithScrapingBee(string $url): array
    {
        try {
            $params = [
                'api_key' => $this->apiKey,
                'url' => $url,
                'render_js' => 'true',
            ];

            $timeout = 90;

            $response = Http::timeout($timeout)->get('https://app.scrapingbee.com/api/v1/', $params);

            if ($response->successful()) {
                $html = $response->body();
                $content = $this->extractTextContent($html);

                return [
                    'success' => true,
                    'content' => $content,
                    'html' => $html,
                    'url' => $url,
                ];
            }

            $errorBody = $response->body();
            Log::error('❌ ScraperService: ScrapingBee API Error', [
                'url' => $url,
                'status' => $response->status(),
                'body' => substr($errorBody, 0, 500),
            ]);

            return [
                'success' => false,
                'status' => $response->status(),
                'error' => 'Failed to scrape website with ScrapingBee: HTTP ' . $response->status() . ' - ' . substr($errorBody, 0, 200),
            ];
        } catch (\Exception $e) {
            $isTimeout = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'timeout');

            Log::error('❌ ScraperService: ScrapingBee Exception', [
                'url' => $url,
                'message' => $e->getMessage(),
                'is_timeout' => $isTimeout,
            ]);

            return [
                'success' => false,
                'status' => $isTimeout ? 408 : 500,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract text content from HTML
     */
    protected function extractTextContent(string $html): string
    {
        // Remove scripts and styles
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);

        // Extract text from common content tags
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Limit to reasonable length (50k chars)
        return mb_substr($text, 0, 50000);
    }
}

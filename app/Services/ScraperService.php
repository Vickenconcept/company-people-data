<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScraperService
{
    protected string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.scraperapi.api_key');
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
     * Automatically retries with premium mode for protected sites (Cloudflare, etc.)
     */
    public function scrapeWebsite(string $url): array
    {
        Log::info('🌐 ScraperService: Starting website scrape', [
            'url' => $url,
            'has_api_key' => !empty($this->apiKey),
        ]);

        // Try standard scrape first
        $result = $this->attemptScrape($url, false);
        
        // If it fails with 500 and mentions protected domains, try premium mode
        if (!$result['success'] && 
            $result['status'] === 500 && 
            (str_contains($result['error'] ?? '', 'Protected domains') || 
             str_contains($result['error'] ?? '', 'premium') ||
             str_contains($result['error'] ?? '', 'Cloudflare'))) {
            
            Log::info('🔄 ScraperService: Protected domain detected, trying premium mode', [
                'url' => $url,
            ]);
            
            // Try with premium mode
            $premiumResult = $this->attemptScrape($url, true);
            
            // If premium also fails, try ultra_premium
            if (!$premiumResult['success'] && 
                $premiumResult['status'] === 500) {
                
                Log::info('🔄 ScraperService: Premium mode failed, trying ultra_premium mode', [
                    'url' => $url,
                ]);
                
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
                Log::info('🌐 ScraperService: Using ultra_premium mode', ['url' => $url]);
            } elseif ($premium) {
                $params['premium'] = 'true';
                Log::info('🌐 ScraperService: Using premium mode', ['url' => $url]);
            }
            
            // Increase timeout to 90 seconds for premium modes (they take longer)
            $timeout = ($premium || $ultraPremium) ? 90 : 60;
            
            $response = Http::timeout($timeout)->get('http://api.scraperapi.com', $params);

            Log::info('🌐 ScraperService: API response received', [
                'url' => $url,
                'status' => $response->status(),
                'response_size' => strlen($response->body()),
                'premium_mode' => $premium || $ultraPremium,
            ]);

            if ($response->successful()) {
                $html = $response->body();
                $content = $this->extractTextContent($html);

                Log::info('✅ ScraperService: Website scraped successfully', [
                    'url' => $url,
                    'html_length' => strlen($html),
                    'content_length' => strlen($content),
                    'premium_mode' => $premium || $ultraPremium,
                ]);

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

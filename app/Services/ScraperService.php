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
     */
    public function scrapeWebsite(string $url): array
    {
        Log::info('🌐 ScraperService: Starting website scrape', [
            'url' => $url,
            'has_api_key' => !empty($this->apiKey),
        ]);

        try {
            $response = Http::timeout(30)->get('http://api.scraperapi.com', [
                'api_key' => $this->apiKey,
                'url' => $url,
                'render' => 'true', // Render JavaScript
            ]);

            Log::info('🌐 ScraperService: API response received', [
                'url' => $url,
                'status' => $response->status(),
                'response_size' => strlen($response->body()),
            ]);

            if ($response->successful()) {
                $html = $response->body();
                $content = $this->extractTextContent($html);

                Log::info('✅ ScraperService: Website scraped successfully', [
                    'url' => $url,
                    'html_length' => strlen($html),
                    'content_length' => strlen($content),
                ]);

                return [
                    'success' => true,
                    'content' => $content,
                    'html' => $html,
                    'url' => $url,
                ];
            }

            Log::error('❌ ScraperService: API Error', [
                'url' => $url,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500), // First 500 chars
            ]);

            return [
                'success' => false,
                'error' => 'Failed to scrape website: HTTP ' . $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('❌ ScraperService: Exception', [
                'url' => $url,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
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

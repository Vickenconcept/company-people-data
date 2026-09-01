<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected ?string $apiKey;

    protected string $provider;

    protected string $baseUrl;

    protected string $model;

    protected string $embeddingModel;

    public function __construct(?string $apiKey = null, ?string $provider = null)
    {
        $this->provider = $provider ?? (string) config('services.llm.provider', 'openai');
        $this->configureProvider($apiKey);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Set API key from user's stored keys.
     */
    public function setApiKeyFromUser(User $user): self
    {
        $service = $this->provider === 'openrouter' ? 'openrouter' : 'openai';

        $apiKey = $user->apiKeys()
            ->where('service', $service)
            ->where('is_active', true)
            ->first();

        if ($apiKey) {
            $this->apiKey = $apiKey->api_key;
        }

        return $this;
    }

    /**
     * Analyze website content and create ICP profile.
     */
    public function analyzeCompanyAndCreateICP(string $websiteContent, string $companyName, ?string $websiteUrl = null): array
    {
        if (!filled($this->apiKey)) {
            return [
                'success' => false,
                'error' => $this->missingApiKeyMessage(),
            ];
        }

        $prompt = $this->getICPAnalysisPrompt($websiteContent, $companyName, $websiteUrl);

        $result = $this->chatCompletion([
            [
                'role' => 'system',
                'content' => 'You are an expert business analyst specializing in creating Ideal Customer Profiles (ICPs) for B2B companies.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ], [
            'temperature' => 0.7,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (!$result['success']) {
            return $result;
        }

        $icp = json_decode($result['content'], true);

        if (!is_array($icp)) {
            return [
                'success' => false,
                'error' => 'Failed to parse ICP response from LLM.',
            ];
        }

        return [
            'success' => true,
            'icp' => $icp,
        ];
    }

    /**
     * Generate search criteria from ICP.
     */
    public function generateSearchCriteria(array $icpProfile, ?string $country = null): array
    {
        if (!filled($this->apiKey)) {
            return [
                'success' => false,
                'error' => $this->missingApiKeyMessage(),
            ];
        }

        $prompt = $this->getSearchCriteriaPrompt($icpProfile, $country);

        $result = $this->chatCompletion([
            [
                'role' => 'system',
                'content' => 'You are an expert at converting ICP profiles into searchable criteria for company databases.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ], [
            'temperature' => 0.5,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (!$result['success']) {
            return $result;
        }

        $criteria = json_decode($result['content'], true);

        if (!is_array($criteria)) {
            return [
                'success' => false,
                'error' => 'Failed to parse search criteria response from LLM.',
            ];
        }

        return [
            'success' => true,
            'criteria' => $criteria,
        ];
    }

    /**
     * Generate email content for outreach.
     */
    public function generateEmailContent(
        array $personData,
        array $companyData,
        ?string $customMessage = null,
        ?array $senderData = null
    ): array {
        if (!filled($this->apiKey)) {
            return [
                'success' => false,
                'error' => $this->missingApiKeyMessage(),
            ];
        }

        $prompt = $this->getEmailGenerationPrompt($personData, $companyData, $customMessage, $senderData);

        $result = $this->chatCompletion([
            [
                'role' => 'system',
                'content' => 'You are an expert at writing professional, personalized cold emails for B2B outreach.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ], [
            'temperature' => 0.8,
        ]);

        if (!$result['success']) {
            return $result;
        }

        $emailParts = $this->parseEmailContent($result['content']);

        return [
            'success' => true,
            'subject' => $emailParts['subject'],
            'body' => $emailParts['body'],
        ];
    }

    /**
     * Create embeddings for similarity search.
     */
    public function createEmbedding(string $text): ?array
    {
        if (!filled($this->apiKey)) {
            return null;
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(60)
                ->withHeaders($this->requestHeaders())
                ->post("{$this->baseUrl}/embeddings", [
                    'model' => $this->embeddingModel,
                    'input' => $text,
                ]);

            if ($response->successful()) {
                return $response->json('data.0.embedding');
            }

            Log::error('OpenAIService: Embedding API error', [
                'provider' => $this->provider,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('OpenAIService: Embedding exception', [
                'provider' => $this->provider,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $options
     * @return array{success: bool, content?: string, error?: string}
     */
    protected function chatCompletion(array $messages, array $options = []): array
    {
        try {
            /** @var Response $response */
            $response = Http::timeout(60)
                ->withHeaders($this->requestHeaders())
                ->post("{$this->baseUrl}/chat/completions", array_merge([
                    'model' => $this->model,
                    'messages' => $messages,
                ], $options));

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');

                if (!is_string($content) || $content === '') {
                    return [
                        'success' => false,
                        'error' => 'LLM returned an empty response.',
                    ];
                }

                return [
                    'success' => true,
                    'content' => $content,
                ];
            }

            $body = substr($response->body(), 0, 500);

            Log::error('OpenAIService: Chat completion API error', [
                'provider' => $this->provider,
                'model' => $this->model,
                'status' => $response->status(),
                'body' => $body,
            ]);

            return [
                'success' => false,
                'error' => "Failed to call {$this->provider} model {$this->model}: HTTP {$response->status()} — {$body}",
            ];
        } catch (\Exception $e) {
            Log::error('OpenAIService: Chat completion exception', [
                'provider' => $this->provider,
                'model' => $this->model,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function configureProvider(?string $apiKey): void
    {
        if ($this->provider === 'openrouter') {
            $this->baseUrl = 'https://openrouter.ai/api/v1';
            $this->apiKey = $apiKey ?? config('services.openrouter.api_key');
            $this->model = (string) (config('services.llm.model') ?: config('services.openrouter.model', 'openai/gpt-4o-mini'));
            $this->embeddingModel = (string) config('services.openrouter.embedding_model', 'openai/text-embedding-3-small');

            return;
        }

        $this->provider = 'openai';
        $this->baseUrl = 'https://api.openai.com/v1';
        $this->apiKey = $apiKey ?? config('services.openai.api_key');
        $this->model = (string) (config('services.llm.model') ?: config('services.openai.model', 'gpt-4o-mini'));
        $this->embeddingModel = (string) config('services.openai.embedding_model', 'text-embedding-3-small');
    }

    /**
     * @return array<string, string>
     */
    protected function requestHeaders(): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($this->provider === 'openrouter') {
            $headers['HTTP-Referer'] = (string) config('services.openrouter.site_url', config('app.url'));
            $headers['X-Title'] = (string) config('services.openrouter.app_name', config('app.name', 'Laravel'));
        }

        return $headers;
    }

    protected function missingApiKeyMessage(): string
    {
        if ($this->provider === 'openrouter') {
            return 'Missing OpenRouter API key. Set OPENROUTER_API_KEY in .env, then run php artisan config:clear && php artisan queue:restart.';
        }

        return 'Missing OpenAI API key. Set OPENAI_API_KEY in .env, then run php artisan config:clear && php artisan queue:restart.';
    }

    protected function getICPAnalysisPrompt(string $content, string $companyName, ?string $url): string
    {
        return "Analyze the following website content for {$companyName}".($url ? " ({$url})" : '')." and create a comprehensive Ideal Customer Profile (ICP).

Website Content:
{$content}

Please provide a JSON object with the following structure:
{
  \"industry\": \"Primary industry\",
  \"sub_industries\": [\"List of sub-industries\"],
  \"company_size\": \"Small/Medium/Large/Enterprise\",
  \"employee_range\": \"e.g., 50-200\",
  \"target_audience\": \"Description of target customers\",
  \"products_services\": [\"List of main products/services\"],
  \"price_tier\": \"Budget/Mid-range/Premium\",
  \"technologies\": [\"List of technologies used\"],
  \"geographic_focus\": \"Primary markets\",
  \"keywords\": [\"Relevant keywords for similar companies\"],
  \"competitors\": [\"Similar companies\"],
  \"value_proposition\": \"What makes them unique\",
  \"business_model\": \"B2B/B2C/B2B2C\"
}";
    }

    protected function getSearchCriteriaPrompt(array $icp, ?string $country = null): string
    {
        $icpJson = json_encode($icp, JSON_PRETTY_PRINT);
        $countryNote = $country ? "\n\nIMPORTANT: The user has specified to search in country: ".strtoupper($country).'. Use this as the primary country in your response.' : '';

        return "Based on this ICP profile, generate SPECIFIC search criteria for finding similar companies. Focus on the PRIMARY industry and avoid generic tech companies unless the ICP is actually a tech company.

{$icpJson}{$countryNote}

IMPORTANT:
- If the industry is \"Travel and Tourism\", find travel companies, NOT Google/Amazon/LinkedIn
- If the industry is \"E-commerce\", find e-commerce companies, NOT generic tech giants
- Focus on companies that actually match the industry and business model
- Use specific, industry-relevant keywords
- Avoid generic keywords that would match tech giants".($country ? "\n- Primary country should be: ".strtoupper($country) : '')."

Return a JSON object with:
{
  \"industry\": \"Primary industry name (exact match for Apollo API)\",
  \"industries\": [\"Array of 3-5 specific related industries\"],
  \"country\": \"".($country ? strtoupper($country) : '')."\",
  \"countries\": ".($country ? '["'.strtoupper($country).'"]' : '["Array of top 5-10 target countries"]').",
  \"company_size_min\": 0,
  \"company_size_max\": 10000,
  \"keywords\": [\"5-10 SPECIFIC industry-relevant keywords that would find similar companies\"],
  \"technologies\": [\"Technologies specific to this industry\"]
}

Example for Travel company:
- industry: \"Travel and Tourism\"
- keywords: [\"travel booking\", \"tour operator\", \"travel agency\", \"vacation packages\", \"travel experiences\"]
- NOT: [\"technology\", \"software\", \"platform\"]";
    }

    protected function getEmailGenerationPrompt(
        array $person,
        array $company,
        ?string $customMessage,
        ?array $sender
    ): string {
        $personInfo = json_encode($person, JSON_PRETTY_PRINT);
        $companyInfo = json_encode($company, JSON_PRETTY_PRINT);
        $senderInfo = json_encode($sender ?? [], JSON_PRETTY_PRINT);

        $basePrompt = "Write a professional, personalized email.

Recipient Information:
{$personInfo}

Company Information:
{$companyInfo}";

        $basePrompt .= "\n\nSender Information (logged-in user / sender):\n{$senderInfo}";

        if ($customMessage) {
            $basePrompt .= "\n\nCustom Message/Context:\n{$customMessage}";
        }

        $basePrompt .= "\n\nGenerate an email with:
1. A compelling subject line
2. A personalized opening
3. Clear value proposition
4. A soft call-to-action
5. Professional closing

Rules:
- Output BODY as valid HTML only (no Markdown), using simple tags like <p>, <br>, <strong>, and optionally <ul><li>.
- Use the real recipient name/title/company name from the provided data.
- Treat Custom Message/Context as the highest priority instruction from the user/template.
- The subject and body MUST directly reflect the exact intent in Custom Message/Context (sales, job application, partnership, follow-up, support request, etc.).
- Do not switch to a different intent/angle than the one in Custom Message/Context.
- Campaign background is supporting information only; never override the primary instruction with campaign background.
- If context indicates job application/career intent, write as an application email (skills, fit, interest, next-step), NOT a sales or collaboration pitch.
- Avoid phrases like \"our platform\", \"our solution\", \"collaboration opportunity\" unless explicitly requested in Custom Message/Context.
- Do not mention sender company unless a real sender company is explicitly provided in Sender Information or Custom Message/Context.
- Do NOT output bracket-style placeholders like [Your Name] / [Your Company Name] in the final email.
- If a specific field is missing, adapt naturally:
  - If recipient full name is missing: start with \"Hi\" or \"Hello\".
  - If recipient title is missing: omit it (do not add brackets).
  - If recipient company name is missing: refer to \"your company\".
  - If sender name is missing: use \"Best regards,\" only.
- Only if both parties data is unusable (extremely unlikely), you may use a generic sentence; never use bracket placeholders.

Format the response as (exactly this format):
SUBJECT: <subject line>
BODY:
<body html only, no <html> wrapper>";

        return $basePrompt;
    }

    protected function parseEmailContent(string $content): array
    {
        $subject = '';
        $body = '';

        if (preg_match('/SUBJECT:\s*(.+?)(?:\n|$)/i', $content, $matches)) {
            $subject = trim($matches[1]);
        }

        if (preg_match('/BODY:\s*(.+)/is', $content, $matches)) {
            $body = trim($matches[1]);
        } else {
            $body = preg_replace('/SUBJECT:.*?\n/i', '', $content);
            $body = trim($body);
        }

        return [
            'subject' => $subject ?: 'Re: Business Opportunity',
            'body' => $body ?: $content,
        ];
    }
}

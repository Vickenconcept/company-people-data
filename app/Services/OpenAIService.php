<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.openai.api_key');
    }

    /**
     * Set API key from user's stored keys
     */
    public function setApiKeyFromUser(User $user): self
    {
        $apiKey = $user->apiKeys()
            ->where('service', 'openai')
            ->where('is_active', true)
            ->first();

        if ($apiKey) {
            $this->apiKey = $apiKey->api_key;
        }

        return $this;
    }

    /**
     * Analyze website content and create ICP profile
     */
    public function analyzeCompanyAndCreateICP(string $websiteContent, string $companyName, ?string $websiteUrl = null): array
    {
        $prompt = $this->getICPAnalysisPrompt($websiteContent, $companyName, $websiteUrl);

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert business analyst specializing in creating Ideal Customer Profiles (ICPs) for B2B companies.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object'],
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                $icp = json_decode($content, true);

                return [
                    'success' => true,
                    'icp' => $icp,
                ];
            }

            Log::error('❌ OpenAIService: API Error', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to analyze company: HTTP ' . $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('❌ OpenAIService: Exception', [
                'company_name' => $companyName,
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
     * Generate search criteria from ICP
     */
    public function generateSearchCriteria(array $icpProfile, ?string $country = null): array
    {
        $prompt = $this->getSearchCriteriaPrompt($icpProfile, $country);

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert at converting ICP profiles into searchable criteria for company databases.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.5,
                'response_format' => ['type' => 'json_object'],
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                $criteria = json_decode($content, true);

                return [
                    'success' => true,
                    'criteria' => $criteria,
                ];
            }

            Log::error('❌ OpenAIService: Search criteria generation failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate search criteria: HTTP ' . $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('❌ OpenAIService: Search Criteria Exception', [
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
     * Generate email content for outreach
     */
    public function generateEmailContent(
        array $personData,
        array $companyData,
        ?string $customMessage = null,
        ?array $senderData = null
    ): array
    {
        $prompt = $this->getEmailGenerationPrompt($personData, $companyData, $customMessage, $senderData);

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert at writing professional, personalized cold emails for B2B outreach.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.8,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');

                // Extract subject and body
                $emailParts = $this->parseEmailContent($content);

                return [
                    'success' => true,
                    'subject' => $emailParts['subject'],
                    'body' => $emailParts['body'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to generate email',
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI Email Generation Exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create embeddings for similarity search
     */
    public function createEmbedding(string $text): ?array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/embeddings", [
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

            if ($response->successful()) {
                return $response->json('data.0.embedding');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI Embedding Exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function getICPAnalysisPrompt(string $content, string $companyName, ?string $url): string
    {
        return "Analyze the following website content for {$companyName}" . ($url ? " ({$url})" : '') . " and create a comprehensive Ideal Customer Profile (ICP).

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
        $countryNote = $country ? "\n\nIMPORTANT: The user has specified to search in country: " . strtoupper($country) . ". Use this as the primary country in your response." : '';

        return "Based on this ICP profile, generate SPECIFIC search criteria for finding similar companies. Focus on the PRIMARY industry and avoid generic tech companies unless the ICP is actually a tech company.

{$icpJson}{$countryNote}

IMPORTANT:
- If the industry is \"Travel and Tourism\", find travel companies, NOT Google/Amazon/LinkedIn
- If the industry is \"E-commerce\", find e-commerce companies, NOT generic tech giants
- Focus on companies that actually match the industry and business model
- Use specific, industry-relevant keywords
- Avoid generic keywords that would match tech giants" . ($country ? "\n- Primary country should be: " . strtoupper($country) : "") . "

Return a JSON object with:
{
  \"industry\": \"Primary industry name (exact match for Apollo API)\",
  \"industries\": [\"Array of 3-5 specific related industries\"],
  \"country\": \"" . ($country ? strtoupper($country) : "") . "\",
  \"countries\": " . ($country ? "[\"" . strtoupper($country) . "\"]" : "[\"Array of top 5-10 target countries\"]") . ",
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
    ): string
    {
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

        // Try to extract subject
        if (preg_match('/SUBJECT:\s*(.+?)(?:\n|$)/i', $content, $matches)) {
            $subject = trim($matches[1]);
        }

        // Try to extract body
        if (preg_match('/BODY:\s*(.+)/is', $content, $matches)) {
            $body = trim($matches[1]);
        } else {
            // If no BODY tag, use everything after SUBJECT
            $body = preg_replace('/SUBJECT:.*?\n/i', '', $content);
            $body = trim($body);
        }

        return [
            'subject' => $subject ?: 'Re: Business Opportunity',
            'body' => $body ?: $content,
        ];
    }
}


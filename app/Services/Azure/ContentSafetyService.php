<?php

namespace App\Services\Azure;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContentSafetyService
{
    private string $endpoint;
    private string $apiKey;
    private string $apiVersion;

    public function __construct()
    {
        $this->endpoint = rtrim(config('azure.content_safety.endpoint', ''), '/');
        $this->apiKey = config('azure.content_safety.api_key', '');
        $this->apiVersion = config('azure.content_safety.api_version');
    }

    /**
     * Analyze text for harmful content (hate, violence, sexual, self-harm).
     */
    public function analyzeText(string $text): array
    {
        if (!$this->isConfigured()) {
            return $this->passThrough();
        }

        $url = "{$this->endpoint}/contentsafety/text:analyze?api-version={$this->apiVersion}";

        $startTime = microtime(true);

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(15)->post($url, [
            'text' => $text,
            'categories' => ['Hate', 'Violence', 'Sexual', 'SelfHarm'],
            'outputType' => 'FourSeverityLevels',
        ]);

        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            Log::error('Content Safety analysis failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Content safety check failed'),
                'latency_ms' => $latencyMs,
            ];
        }

        $categories = $response->json('categoriesAnalysis', []);
        $results = [];
        $maxSeverity = 0;

        foreach ($categories as $cat) {
            $results[$cat['category']] = $cat['severity'];
            $maxSeverity = max($maxSeverity, $cat['severity']);
        }

        return [
            'success' => true,
            'categories' => $results,
            'max_severity' => $maxSeverity,
            'safe' => $maxSeverity <= 2, // 0-2 acceptable, 4-6 blocked
            'latency_ms' => $latencyMs,
        ];
    }

    /**
     * Prompt Shields — detect jailbreak attempts and indirect injection attacks.
     * Uses Azure Content Safety Prompt Shields API.
     */
    public function shieldPrompt(string $userPrompt, array $documents = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => true,
                'jailbreak_detected' => false,
                'injection_detected' => false,
                'safe' => true,
                'mock' => true,
            ];
        }

        $url = "{$this->endpoint}/contentsafety/text:shieldPrompt?api-version={$this->apiVersion}";

        $startTime = microtime(true);

        $body = [
            'userPrompt' => $userPrompt,
            'documents' => array_map(fn ($doc) => is_string($doc) ? $doc : ($doc['content'] ?? ''), $documents),
        ];

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(15)->post($url, $body);

        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            Log::warning('Prompt Shields check failed — proceeding with caution', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Prompt shield check failed'),
                'latency_ms' => $latencyMs,
                'safe' => true, // Don't block on API failure
            ];
        }

        $data = $response->json();
        $userAttack = $data['userPromptAnalysis']['attackDetected'] ?? false;
        $docAttacks = collect($data['documentsAnalysis'] ?? [])
            ->pluck('attackDetected')
            ->filter()
            ->isNotEmpty();

        return [
            'success' => true,
            'jailbreak_detected' => $userAttack,
            'injection_detected' => $docAttacks,
            'safe' => !$userAttack && !$docAttacks,
            'user_analysis' => $data['userPromptAnalysis'] ?? [],
            'document_analysis' => $data['documentsAnalysis'] ?? [],
            'latency_ms' => $latencyMs,
        ];
    }

    /**
     * Check groundedness of a generated answer against source documents.
     * Uses Azure Content Safety Groundedness Detection API.
     */
    public function checkGroundedness(string $answer, string $groundingSources): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => true,
                'grounded' => true,
                'score' => 1.0,
                'ungrounded_segments' => [],
                'mock' => true,
            ];
        }

        // Groundedness Detection requires api-version 2024-09-15-preview or later
        $groundednessApiVersion = '2024-09-15-preview';
        $url = "{$this->endpoint}/contentsafety/text:detectGroundedness?api-version={$groundednessApiVersion}";

        $startTime = microtime(true);

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($url, [
            'domain' => 'Generic',
            'task' => 'QnA',
            'text' => $answer,
            'groundingSources' => [$groundingSources],
            'reasoning' => true,
        ]);

        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            Log::warning('Groundedness check failed — composite score will redistribute weight to other rings', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 200),
                'url' => $url,
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Groundedness check failed'),
                'status_code' => $response->status(),
                'latency_ms' => $latencyMs,
            ];
        }

        $data = $response->json();
        $ungroundedDetected = $data['ungroundedDetected'] ?? false;
        $ungroundedPercentage = $data['ungroundedPercentage'] ?? 0;
        $ungroundedDetails = $data['ungroundedDetails'] ?? [];

        return [
            'success' => true,
            'grounded' => !$ungroundedDetected,
            'score' => 1.0 - ($ungroundedPercentage / 100),
            'ungrounded_percentage' => $ungroundedPercentage,
            'ungrounded_segments' => $ungroundedDetails,
            'latency_ms' => $latencyMs,
        ];
    }

    private function passThrough(): array
    {
        return [
            'success' => true,
            'categories' => [],
            'max_severity' => 0,
            'safe' => true,
            'mock' => true,
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->endpoint) && !empty($this->apiKey);
    }
}

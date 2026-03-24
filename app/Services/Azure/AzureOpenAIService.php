<?php

namespace App\Services\Azure;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AzureOpenAIService
{
    private string $endpoint;
    private string $apiKey;
    private string $deployment;
    private string $complexDeployment;
    private string $apiVersion;

    public function __construct()
    {
        $this->endpoint = rtrim(config('azure.openai.endpoint'), '/');
        $this->apiKey = config('azure.openai.api_key');
        $this->deployment = config('azure.openai.deployment');
        $this->complexDeployment = config('azure.openai.complex_deployment');
        $this->apiVersion = config('azure.openai.api_version');
    }

    /**
     * Generate a grounded answer using Azure OpenAI with retrieved context.
     */
    public function chatCompletion(
        string $systemPrompt,
        string $userMessage,
        array $context = [],
        bool $useComplex = false,
    ): array {
        $deployment = $useComplex ? $this->complexDeployment : $this->deployment;
        $url = "{$this->endpoint}/openai/deployments/{$deployment}/chat/completions?api-version={$this->apiVersion}";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Inject retrieved context as a system-level grounding message
        if (!empty($context)) {
            $contextText = $this->formatContext($context);
            $messages[] = [
                'role' => 'system',
                'content' => "Use ONLY the following retrieved documents to answer. Cite sources using [Source N] notation.\n\n{$contextText}",
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $startTime = microtime(true);

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($url, [
            'messages' => $messages,
            'temperature' => 0.1, // Low temperature for factual, grounded responses
            'max_tokens' => 2048,
            'top_p' => 0.95,
        ]);

        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            Log::error('Azure OpenAI request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Azure OpenAI request failed'),
                'latency_ms' => $latencyMs,
            ];
        }

        $data = $response->json();
        $choice = $data['choices'][0] ?? null;

        return [
            'success' => true,
            'answer' => $choice['message']['content'] ?? '',
            'finish_reason' => $choice['finish_reason'] ?? 'unknown',
            'token_count' => $data['usage']['total_tokens'] ?? 0,
            'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
            'latency_ms' => $latencyMs,
        ];
    }

    /**
     * Generate embeddings for a text string.
     */
    public function embeddings(string $text): array
    {
        $url = "{$this->endpoint}/openai/deployments/text-embedding-ada-002/embeddings?api-version={$this->apiVersion}";

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($url, [
            'input' => $text,
        ]);

        if ($response->failed()) {
            return ['success' => false, 'error' => $response->json('error.message', 'Embedding failed')];
        }

        return [
            'success' => true,
            'embedding' => $response->json('data.0.embedding', []),
        ];
    }

    private function formatContext(array $chunks): string
    {
        $parts = [];
        foreach ($chunks as $i => $chunk) {
            $source = $chunk['title'] ?? ('Source ' . ($i + 1));
            $page = isset($chunk['page']) ? " (Page {$chunk['page']})" : '';
            $parts[] = "[Source " . ($i + 1) . ": {$source}{$page}]\n{$chunk['content']}";
        }
        return implode("\n\n---\n\n", $parts);
    }

    public function isConfigured(): bool
    {
        return !empty($this->endpoint) && !empty($this->apiKey);
    }
}

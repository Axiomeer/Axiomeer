<?php

namespace App\Services\Azure;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AzureSearchService
{
    private string $endpoint;
    private string $apiKey;
    private string $index;
    private string $apiVersion;
    private string $semanticConfig;

    public function __construct()
    {
        $this->endpoint = rtrim(config('azure.search.endpoint', ''), '/');
        $this->apiKey = config('azure.search.api_key', '');
        $this->index = config('azure.search.index');
        $this->apiVersion = config('azure.search.api_version');
        $this->semanticConfig = config('azure.search.semantic_config');
    }

    /**
     * Perform a hybrid search (keyword + semantic) against Azure AI Search.
     */
    public function search(string $query, string $domainSlug = null, int $topK = 5): array
    {
        if (!$this->isConfigured()) {
            return $this->mockSearch($query, $topK);
        }

        $url = "{$this->endpoint}/indexes/{$this->index}/docs/search?api-version={$this->apiVersion}";

        $body = [
            'search' => $query,
            'queryType' => 'semantic',
            'semanticConfiguration' => $this->semanticConfig,
            'top' => $topK,
            'select' => 'id,title,content,page_number,chunk_index,domain,document_id',
            'captions' => 'extractive',
            'answers' => 'extractive|count-3',
        ];

        // Filter by domain if specified
        if ($domainSlug) {
            $body['filter'] = "domain eq '{$domainSlug}'";
        }

        $startTime = microtime(true);

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($url, $body);

        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            Log::error('Azure AI Search request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Search failed'),
                'latency_ms' => $latencyMs,
            ];
        }

        $results = $response->json('value', []);

        $chunks = array_map(function ($result) {
            return [
                'id' => $result['id'] ?? null,
                'title' => $result['title'] ?? 'Unknown',
                'content' => $result['content'] ?? '',
                'page' => $result['page_number'] ?? null,
                'chunk_index' => $result['chunk_index'] ?? null,
                'domain' => $result['domain'] ?? null,
                'document_id' => $result['document_id'] ?? null,
                'score' => $result['@search.score'] ?? 0,
                'reranker_score' => $result['@search.rerankerScore'] ?? null,
                'captions' => $result['@search.captions'] ?? [],
            ];
        }, $results);

        return [
            'success' => true,
            'chunks' => $chunks,
            'count' => count($chunks),
            'latency_ms' => $latencyMs,
        ];
    }

    /**
     * Mock search for development when Azure AI Search is not configured.
     */
    private function mockSearch(string $query, int $topK): array
    {
        Log::info('Azure AI Search not configured — returning mock results', ['query' => $query]);

        return [
            'success' => true,
            'chunks' => [
                [
                    'id' => 'mock-1',
                    'title' => 'Sample Compliance Document',
                    'content' => "This is a mock search result for the query: \"{$query}\". In production, this would contain relevant document chunks retrieved from Azure AI Search using hybrid (keyword + semantic) search with reranking.",
                    'page' => 1,
                    'chunk_index' => 0,
                    'domain' => null,
                    'document_id' => null,
                    'score' => 0.95,
                    'reranker_score' => 0.92,
                    'captions' => [],
                ],
            ],
            'count' => 1,
            'latency_ms' => 0,
            'mock' => true,
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->endpoint) && !empty($this->apiKey);
    }
}

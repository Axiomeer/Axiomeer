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

    public function __construct()
    {
        $this->endpoint = rtrim(config('azure.search.endpoint', ''), '/');
        $this->apiKey = config('azure.search.api_key', '');
        $this->index = config('azure.search.index');
        $this->apiVersion = config('azure.search.api_version');
    }

    /**
     * Perform a keyword search against Azure AI Search.
     */
    public function search(string $query, string $domainSlug = null, int $topK = 5): array
    {
        if (!$this->isConfigured()) {
            return $this->mockSearch($query, $topK);
        }

        $url = "{$this->endpoint}/indexes/{$this->index}/docs/search?api-version={$this->apiVersion}";

        $body = [
            'search' => $query,
            'queryType' => 'simple',
            'top' => $topK,
            'select' => 'id,title,content,page_number,chunk_index,domain,document_id',
        ];

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
                'reranker_score' => null,
                'captions' => [],
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
     * Push document chunks to the search index.
     */
    public function indexDocumentChunks(string $documentId, string $title, string $domain, array $chunks): array
    {
        if (!$this->isConfigured()) {
            Log::info('Azure AI Search not configured — skipping indexing');
            return ['success' => true, 'indexed' => 0, 'mock' => true];
        }

        $url = "{$this->endpoint}/indexes/{$this->index}/docs/index?api-version={$this->apiVersion}";

        $actions = [];
        foreach ($chunks as $chunk) {
            $actions[] = [
                '@search.action' => 'mergeOrUpload',
                'id' => $documentId . '-' . ($chunk['chunk_index'] ?? 0),
                'title' => $title,
                'content' => $chunk['content'] ?? '',
                'page_number' => $chunk['page'] ?? null,
                'chunk_index' => $chunk['chunk_index'] ?? 0,
                'domain' => $domain,
                'document_id' => $documentId,
            ];
        }

        // Batch in groups of 1000 (Azure limit)
        $indexed = 0;
        foreach (array_chunk($actions, 1000) as $batch) {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, ['value' => $batch]);

            if ($response->failed()) {
                Log::error('Azure AI Search indexing failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error' => $response->json('error.message', 'Indexing failed'),
                    'indexed' => $indexed,
                ];
            }

            $indexed += count($batch);
        }

        return ['success' => true, 'indexed' => $indexed];
    }

    /**
     * Remove all chunks for a document from the index.
     */
    public function removeDocument(string $documentId, int $chunkCount): array
    {
        if (!$this->isConfigured()) {
            return ['success' => true, 'mock' => true];
        }

        $url = "{$this->endpoint}/indexes/{$this->index}/docs/index?api-version={$this->apiVersion}";

        $actions = [];
        for ($i = 0; $i < $chunkCount; $i++) {
            $actions[] = [
                '@search.action' => 'delete',
                'id' => $documentId . '-' . $i,
            ];
        }

        if (empty($actions)) {
            return ['success' => true, 'removed' => 0];
        }

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($url, ['value' => $actions]);

        return ['success' => !$response->failed(), 'removed' => count($actions)];
    }

    private function mockSearch(string $query, int $topK): array
    {
        Log::info('Azure AI Search not configured — returning mock results', ['query' => $query]);

        return [
            'success' => true,
            'chunks' => [
                [
                    'id' => 'mock-1',
                    'title' => 'Sample Compliance Document',
                    'content' => "This is a mock search result for the query: \"{$query}\". In production, this would contain relevant document chunks retrieved from Azure AI Search.",
                    'page' => 1,
                    'chunk_index' => 0,
                    'domain' => null,
                    'document_id' => null,
                    'score' => 0.95,
                    'reranker_score' => null,
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

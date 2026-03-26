<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebSearchService
{
    private string $apiKey;
    private string $apiHost;
    private bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('services.bing_search.api_key', '');
        $this->apiHost = config('services.bing_search.api_host', 'bing-search-apis.p.rapidapi.com');
        $this->enabled = !empty($this->apiKey);
    }

    public function isConfigured(): bool
    {
        return $this->enabled;
    }

    /**
     * Search the web using Bing Search API (via RapidAPI) to cross-reference claims.
     */
    public function search(string $query, int $count = 5): array
    {
        if (!$this->enabled) {
            return $this->mockSearch($query, $count);
        }

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-rapidapi-key' => $this->apiKey,
                'x-rapidapi-host' => $this->apiHost,
            ])->timeout(10)->get("https://{$this->apiHost}/api/rapid/web_search", [
                'keyword' => $query,
                'page' => 0,
                'size' => $count,
            ]);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->failed()) {
                Log::warning('Bing Search API failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 200),
                ]);
                return $this->mockSearch($query, $count, $latencyMs);
            }

            $data = $response->json();
            $results = [];

            foreach ($data['data']['items'] ?? [] as $item) {
                $results[] = [
                    'title' => $item['title'] ?? '',
                    'url' => $item['link'] ?? '',
                    'snippet' => $item['description'] ?? '',
                    'date' => null,
                ];
            }

            return [
                'success' => true,
                'results' => array_slice($results, 0, $count),
                'query' => $query,
                'total_estimated' => count($results),
                'latency_ms' => $latencyMs,
                'mock' => false,
            ];
        } catch (\Throwable $e) {
            Log::warning('Bing Search exception', ['error' => $e->getMessage()]);
            return $this->mockSearch($query, 0);
        }
    }

    /**
     * Cross-reference a claim against web search results.
     * Returns whether web sources support or contradict the claim.
     */
    public function crossReferenceClaim(string $claim, string $context = ''): array
    {
        $searchQuery = $claim;
        if ($context) {
            $searchQuery .= ' ' . $context;
        }

        $results = $this->search($searchQuery, 3);

        return [
            'claim' => $claim,
            'web_results' => $results['results'] ?? [],
            'sources_found' => count($results['results'] ?? []),
            'mock' => $results['mock'] ?? true,
            'latency_ms' => $results['latency_ms'] ?? 0,
        ];
    }

    private function mockSearch(string $query, int $count = 5, int $latencyMs = 0): array
    {
        // Provide a realistic mock response for demo purposes
        $mockResults = [
            [
                'title' => 'Related information from web source',
                'url' => 'https://example.com/search-result-1',
                'snippet' => 'Web search results for: "' . substr($query, 0, 60) . '". This is a mock result — configure BING_SEARCH_API_KEY in .env to enable live web cross-referencing via RapidAPI.',
                'date' => now()->toIso8601String(),
            ],
            [
                'title' => 'Cross-reference source',
                'url' => 'https://example.com/search-result-2',
                'snippet' => 'Additional web context would appear here when Bing Search API is connected. This helps verify claims against publicly available information.',
                'date' => now()->toIso8601String(),
            ],
        ];

        return [
            'success' => true,
            'results' => array_slice($mockResults, 0, min($count, 2)),
            'query' => $query,
            'total_estimated' => 2,
            'latency_ms' => $latencyMs ?: 50,
            'mock' => true,
        ];
    }
}

<?php

namespace App\Services\Azure;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentIntelligenceService
{
    private string $endpoint;
    private string $apiKey;
    private string $apiVersion;

    public function __construct()
    {
        $this->endpoint = rtrim(config('azure.document_intelligence.endpoint', ''), '/');
        $this->apiKey = config('azure.document_intelligence.api_key', '');
        $this->apiVersion = config('azure.document_intelligence.api_version');
    }

    /**
     * Analyze a document using the prebuilt-layout model.
     * Supports: PDF, JPEG, PNG, BMP, TIFF, HEIF, DOCX, XLSX, PPTX, HTML.
     */
    public function analyzeDocument(string $storagePath): array
    {
        if (!$this->isConfigured()) {
            return $this->mockAnalysis($storagePath);
        }

        $filePath = Storage::disk('local')->path($storagePath);
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $storagePath];
        }

        $fileContents = file_get_contents($filePath);
        $mimeType = mime_content_type($filePath);

        // Submit analysis request
        $url = "{$this->endpoint}/documentintelligence/documentModels/prebuilt-layout:analyze?api-version={$this->apiVersion}";

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->apiKey,
            'Content-Type' => $mimeType,
        ])->timeout(60)->withBody($fileContents, $mimeType)->post($url);

        if ($response->status() !== 202) {
            Log::error('Document Intelligence submission failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['success' => false, 'error' => 'Analysis submission failed: ' . $response->status()];
        }

        // Poll for result
        $operationUrl = $response->header('Operation-Location');
        if (!$operationUrl) {
            return ['success' => false, 'error' => 'No operation location returned'];
        }

        return $this->pollForResult($operationUrl);
    }

    /**
     * Poll the operation URL until analysis completes.
     */
    private function pollForResult(string $operationUrl, int $maxAttempts = 30): array
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(2);

            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
            ])->timeout(30)->get($operationUrl);

            if ($response->failed()) {
                continue;
            }

            $data = $response->json();
            $status = $data['status'] ?? 'unknown';

            if ($status === 'succeeded') {
                return $this->parseResult($data['analyzeResult'] ?? []);
            }

            if ($status === 'failed') {
                return ['success' => false, 'error' => $data['error']['message'] ?? 'Analysis failed'];
            }
        }

        return ['success' => false, 'error' => 'Analysis timed out'];
    }

    /**
     * Parse the analysis result into structured chunks.
     */
    private function parseResult(array $analyzeResult): array
    {
        $content = $analyzeResult['content'] ?? '';
        $pages = $analyzeResult['pages'] ?? [];
        $tables = $analyzeResult['tables'] ?? [];
        $paragraphs = $analyzeResult['paragraphs'] ?? [];

        // Build chunks from paragraphs (better for RAG than raw content)
        $chunks = [];
        $currentChunk = '';
        $currentPage = 1;
        $chunkIndex = 0;

        foreach ($paragraphs as $para) {
            $text = $para['content'] ?? '';
            $pageNum = $para['boundingRegions'][0]['pageNumber'] ?? $currentPage;

            // Start new chunk if current exceeds ~500 chars or page changes
            if (strlen($currentChunk) > 500 || ($pageNum !== $currentPage && !empty($currentChunk))) {
                $chunks[] = [
                    'content' => trim($currentChunk),
                    'page' => $currentPage,
                    'chunk_index' => $chunkIndex++,
                ];
                $currentChunk = '';
                $currentPage = $pageNum;
            }

            $currentChunk .= $text . "\n";
            $currentPage = $pageNum;
        }

        // Add final chunk
        if (!empty(trim($currentChunk))) {
            $chunks[] = [
                'content' => trim($currentChunk),
                'page' => $currentPage,
                'chunk_index' => $chunkIndex,
            ];
        }

        // Extract tables as additional chunks
        foreach ($tables as $i => $table) {
            $tableText = $this->formatTable($table);
            if (!empty($tableText)) {
                $pageNum = $table['boundingRegions'][0]['pageNumber'] ?? 1;
                $chunks[] = [
                    'content' => "[Table " . ($i + 1) . "]\n" . $tableText,
                    'page' => $pageNum,
                    'chunk_index' => count($chunks),
                ];
            }
        }

        return [
            'success' => true,
            'content' => $content,
            'page_count' => count($pages),
            'chunk_count' => count($chunks),
            'chunks' => $chunks,
            'tables_found' => count($tables),
            'paragraphs_found' => count($paragraphs),
        ];
    }

    private function formatTable(array $table): string
    {
        $cells = $table['cells'] ?? [];
        if (empty($cells)) return '';

        $rows = [];
        foreach ($cells as $cell) {
            $r = $cell['rowIndex'] ?? 0;
            $c = $cell['columnIndex'] ?? 0;
            $rows[$r][$c] = $cell['content'] ?? '';
        }

        ksort($rows);
        $lines = [];
        foreach ($rows as $row) {
            ksort($row);
            $lines[] = implode(' | ', $row);
        }

        return implode("\n", $lines);
    }

    private function mockAnalysis(string $storagePath): array
    {
        $ext = pathinfo($storagePath, PATHINFO_EXTENSION);
        Log::info('Document Intelligence not configured — returning mock analysis', ['path' => $storagePath]);

        return [
            'success' => true,
            'content' => "Mock extracted content from {$ext} file. In production, Azure Document Intelligence would parse this document into structured text, tables, and key-value pairs.",
            'page_count' => 1,
            'chunk_count' => 1,
            'chunks' => [
                [
                    'content' => "Mock content from uploaded {$ext} document. Configure Azure Document Intelligence to enable real extraction.",
                    'page' => 1,
                    'chunk_index' => 0,
                ],
            ],
            'tables_found' => 0,
            'paragraphs_found' => 1,
            'mock' => true,
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->endpoint) && !empty($this->apiKey);
    }
}

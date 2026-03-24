<?php

namespace App\Services;

use App\Models\AgentRun;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\Query;
use App\Models\QueryCitation;
use App\Services\Azure\AzureOpenAIService;
use App\Services\Azure\AzureSearchService;
use App\Services\Azure\ContentSafetyService;
use Illuminate\Support\Facades\Log;

class RAGPipelineService
{
    public function __construct(
        private AzureOpenAIService $openai,
        private AzureSearchService $search,
        private ContentSafetyService $safety,
    ) {}

    /**
     * Execute the full RAG pipeline for a query.
     *
     * Pipeline stages:
     * 1. Content Safety — screen user input
     * 2. Retrieval Agent — fetch relevant chunks from Azure AI Search
     * 3. Generation Agent — produce grounded answer via Azure OpenAI
     * 4. Verification Agent — check groundedness + hallucination defense
     * 5. Persist results — save answer, scores, citations, audit log
     */
    public function process(Query $query): Query
    {
        $query->update(['status' => 'processing']);
        $domain = $query->domain;
        $traceId = uniqid('trace-', true);

        try {
            // ── Stage 1: Content Safety Screen ──
            $safetyRun = $this->logAgentStart($query, 'content_safety', $traceId);
            $inputSafety = $this->safety->analyzeText($query->question);

            if ($inputSafety['success'] && !$inputSafety['safe']) {
                $this->logAgentEnd($safetyRun, 'completed', $inputSafety);
                return $this->failQuery($query, 'Input blocked by content safety filter.', 'red');
            }
            $this->logAgentEnd($safetyRun, 'completed', $inputSafety);

            // ── Stage 2: Retrieval Agent ──
            $retrievalRun = $this->logAgentStart($query, 'retrieval', $traceId);
            $searchResult = $this->search->search($query->question, $domain->slug ?? null, 5);

            if (!$searchResult['success']) {
                $this->logAgentEnd($retrievalRun, 'failed', $searchResult);
                return $this->failQuery($query, 'Document retrieval failed.');
            }
            $this->logAgentEnd($retrievalRun, 'completed', $searchResult, $searchResult['latency_ms'] ?? 0);

            $chunks = $searchResult['chunks'] ?? [];
            $query->update(['retrieved_chunks' => $chunks]);

            // ── Stage 3: Generation Agent ──
            $generationRun = $this->logAgentStart($query, 'generation', $traceId);
            $systemPrompt = $this->buildSystemPrompt($domain);

            $genResult = $this->openai->chatCompletion(
                $systemPrompt,
                $query->question,
                $chunks,
                useComplex: false,
            );

            if (!$genResult['success']) {
                $this->logAgentEnd($generationRun, 'failed', $genResult);
                return $this->failQuery($query, 'Answer generation failed: ' . ($genResult['error'] ?? 'Unknown'));
            }
            $this->logAgentEnd($generationRun, 'completed', $genResult, $genResult['latency_ms'] ?? 0, $genResult['token_count'] ?? 0);

            $answer = $genResult['answer'];

            // ── Stage 4: Verification Agent (Three-Ring Defense) ──
            $verificationRun = $this->logAgentStart($query, 'verification', $traceId);

            // Ring 1: Azure Groundedness API
            $groundingSources = collect($chunks)->pluck('content')->implode("\n\n");
            $groundedness = $this->safety->checkGroundedness($answer, $groundingSources);
            $groundednessScore = $groundedness['score'] ?? null;

            // Ring 2: Output Content Safety
            $outputSafety = $this->safety->analyzeText($answer);

            // Ring 3: Composite scoring (LettuceDetect + SRLM would plug in here)
            // For now, simulate with groundedness as primary signal
            $lettuceScore = $groundednessScore; // Placeholder until LettuceDetect integration
            $confidenceScore = $this->estimateConfidence($chunks, $genResult);

            $compositeSafety = $this->computeCompositeSafety($groundednessScore, $lettuceScore, $confidenceScore);
            $safetyLevel = $this->determineSafetyLevel($compositeSafety);

            $this->logAgentEnd($verificationRun, 'completed', [
                'groundedness' => $groundedness,
                'output_safety' => $outputSafety,
                'composite' => $compositeSafety,
            ], ($groundedness['latency_ms'] ?? 0) + ($outputSafety['latency_ms'] ?? 0));

            // ── Stage 5: Persist Results ──
            $totalLatency = ($searchResult['latency_ms'] ?? 0)
                + ($genResult['latency_ms'] ?? 0)
                + ($groundedness['latency_ms'] ?? 0);

            $query->update([
                'answer' => $safetyLevel === 'red' ? 'This answer was blocked because it could not be verified against source documents.' : $answer,
                'status' => 'completed',
                'groundedness_score' => $groundednessScore,
                'lettuce_score' => $lettuceScore,
                'confidence_score' => $confidenceScore,
                'composite_safety_score' => $compositeSafety,
                'safety_level' => $safetyLevel,
                'token_count' => $genResult['token_count'] ?? 0,
                'latency_ms' => $totalLatency,
                'provenance_dag' => $this->buildProvenanceDag($traceId, $chunks, $genResult, $groundedness),
            ]);

            // Save citations
            $this->saveCitations($query, $chunks);

            // Audit log
            AuditLog::create([
                'user_id' => $query->user_id,
                'query_id' => $query->id,
                'action' => 'query_completed',
                'entity_type' => 'query',
                'entity_id' => $query->id,
                'description' => "Query processed with safety level: {$safetyLevel}",
                'details' => [
                    'safety_level' => $safetyLevel,
                    'composite_score' => $compositeSafety,
                    'token_count' => $genResult['token_count'] ?? 0,
                    'latency_ms' => $totalLatency,
                ],
                'severity' => $safetyLevel === 'red' ? 'warning' : 'info',
            ]);

        } catch (\Throwable $e) {
            Log::error('RAG pipeline error', ['query_id' => $query->id, 'error' => $e->getMessage()]);
            return $this->failQuery($query, 'Pipeline error: ' . $e->getMessage());
        }

        return $query->fresh();
    }

    private function buildSystemPrompt(Domain $domain): string
    {
        $base = $domain->system_prompt ?? 'You are a knowledgeable assistant. Answer questions accurately using only the provided sources.';

        return $base . "\n\n" .
            "RULES:\n" .
            "- ONLY use information from the provided source documents.\n" .
            "- Cite every claim using [Source N] notation.\n" .
            "- If the sources do not contain enough information to answer, say so explicitly.\n" .
            "- Never fabricate facts, statistics, or references.\n" .
            "- Use " . ($domain->citation_format ?? 'inline') . " citation format.";
    }

    private function estimateConfidence(array $chunks, array $genResult): float
    {
        // Simple heuristic: average search score normalized to 0-1
        if (empty($chunks)) {
            return 0.3;
        }

        $scores = array_filter(array_column($chunks, 'reranker_score'));
        if (empty($scores)) {
            $scores = array_column($chunks, 'score');
        }

        $avgScore = !empty($scores) ? array_sum($scores) / count($scores) : 0.5;

        // Clamp to 0-1 range (reranker scores can be 0-4)
        return min(1.0, max(0.0, $avgScore / 4.0));
    }

    private function computeCompositeSafety(?float $groundedness, ?float $lettuce, ?float $confidence): float
    {
        // Weighted average: Azure Groundedness 50%, LettuceDetect 30%, Confidence 20%
        $g = $groundedness ?? 0.5;
        $l = $lettuce ?? 0.5;
        $c = $confidence ?? 0.5;

        return ($g * 0.50) + ($l * 0.30) + ($c * 0.20);
    }

    private function determineSafetyLevel(float $composite): string
    {
        if ($composite >= 0.75) return 'green';
        if ($composite >= 0.45) return 'yellow';
        return 'red';
    }

    private function buildProvenanceDag(string $traceId, array $chunks, array $genResult, array $groundedness): array
    {
        return [
            'trace_id' => $traceId,
            'nodes' => [
                ['id' => 'input', 'type' => 'question', 'label' => 'User Query'],
                ['id' => 'retrieval', 'type' => 'agent', 'label' => 'Retrieval Agent', 'chunks' => count($chunks)],
                ['id' => 'generation', 'type' => 'agent', 'label' => 'Generation Agent', 'tokens' => $genResult['token_count'] ?? 0],
                ['id' => 'verification', 'type' => 'agent', 'label' => 'Verification Agent', 'score' => $groundedness['score'] ?? null],
                ['id' => 'output', 'type' => 'answer', 'label' => 'Grounded Answer'],
            ],
            'edges' => [
                ['from' => 'input', 'to' => 'retrieval'],
                ['from' => 'retrieval', 'to' => 'generation'],
                ['from' => 'generation', 'to' => 'verification'],
                ['from' => 'verification', 'to' => 'output'],
            ],
        ];
    }

    private function saveCitations(Query $query, array $chunks): void
    {
        foreach ($chunks as $i => $chunk) {
            QueryCitation::create([
                'query_id' => $query->id,
                'document_id' => $chunk['document_id'] ?? null,
                'source_snippet' => $chunk['content'] ?? '',
                'document_title' => $chunk['title'] ?? 'Source ' . ($i + 1),
                'page_number' => $chunk['page'] ?? null,
                'chunk_index' => $chunk['chunk_index'] ?? $i,
                'relevance_score' => isset($chunk['reranker_score'])
                    ? min(1.0, $chunk['reranker_score'] / 4.0)
                    : ($chunk['score'] ?? 0),
                'verdict' => 'pending',
            ]);
        }
    }

    private function logAgentStart(Query $query, string $agentType, string $traceId): AgentRun
    {
        return AgentRun::create([
            'query_id' => $query->id,
            'agent_type' => $agentType,
            'status' => 'running',
            'trace_id' => $traceId,
            'span_id' => uniqid('span-'),
        ]);
    }

    private function logAgentEnd(AgentRun $run, string $status, array $output, int $latencyMs = 0, int $tokens = 0): void
    {
        $run->update([
            'status' => $status,
            'output' => $output,
            'latency_ms' => $latencyMs,
            'token_count' => $tokens,
        ]);
    }

    private function failQuery(Query $query, string $reason, string $safetyLevel = null): Query
    {
        $query->update([
            'status' => 'failed',
            'answer' => $reason,
            'safety_level' => $safetyLevel,
        ]);

        AuditLog::create([
            'user_id' => $query->user_id,
            'query_id' => $query->id,
            'action' => 'query_failed',
            'entity_type' => 'query',
            'entity_id' => $query->id,
            'description' => $reason,
            'severity' => 'error',
        ]);

        return $query->fresh();
    }
}

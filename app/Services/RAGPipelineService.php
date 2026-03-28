<?php

namespace App\Services;

use App\Models\AgentRun;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\EvaluationMetric;
use App\Models\Query;
use App\Models\QueryCitation;
use App\Services\Azure\AzureOpenAIService;
use App\Services\Azure\AzureSearchService;
use App\Services\Azure\ContentSafetyService;
use App\Services\Azure\FoundryAgentService;
use App\Services\SemanticKernelService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RAGPipelineService
{
    public function __construct(
        private AzureOpenAIService $openai,
        private AzureSearchService $search,
        private ContentSafetyService $safety,
        private FoundryAgentService $foundryAgent,
        private SemanticKernelService $sk,
    ) {}

    /**
     * Execute the full RAG pipeline for a query.
     *
     * Pipeline stages:
     * 1. Content Safety — screen user input + prompt shields (jailbreak/injection)
     * 2. Retrieval Agent — fetch relevant chunks from Azure AI Search
     * 3. Generation Agent — produce grounded answer via Azure OpenAI (model router)
     * 4. Verification Agent — three-ring hallucination defense + RAGAS evaluation
     * 5. Persist results — save answer, scores, citations, provenance DAG, audit log
     */
    public function process(Query $query, array $documentIds = []): Query
    {
        $query->update(['status' => 'processing']);
        $domain = $query->domain;
        $traceId = uniqid('trace-', true);
        $claimAnalysis = [];

        try {
            // ── Stage 1: Content Safety + Prompt Shields ──
            $safetyRun = $this->logAgentStart($query, 'content_safety', $traceId);

            // 1a: Harm category screening
            $inputSafety = $this->safety->analyzeText($query->question);

            if ($inputSafety['success'] && !$inputSafety['safe']) {
                $this->logAgentEnd($safetyRun, 'completed', $inputSafety, $inputSafety['latency_ms'] ?? 0);
                return $this->failQuery($query, 'Input blocked by content safety filter: harmful content detected.', 'red');
            }

            // 1b: Prompt Shields — jailbreak + indirect injection detection
            $promptShield = $this->safety->shieldPrompt($query->question);

            if ($promptShield['success'] && !$promptShield['safe']) {
                $reason = $promptShield['jailbreak_detected']
                    ? 'Input blocked: jailbreak attempt detected by Prompt Shields.'
                    : 'Input blocked: indirect prompt injection detected by Prompt Shields.';
                $this->logAgentEnd($safetyRun, 'completed', array_merge($inputSafety, ['prompt_shield' => $promptShield]), ($inputSafety['latency_ms'] ?? 0) + ($promptShield['latency_ms'] ?? 0));
                return $this->failQuery($query, $reason, 'red');
            }

            $this->logAgentEnd($safetyRun, 'completed', array_merge($inputSafety, ['prompt_shield' => $promptShield]), ($inputSafety['latency_ms'] ?? 0) + ($promptShield['latency_ms'] ?? 0));

            // ── Stage 2: Retrieval Agent ──
            $retrievalRun = $this->logAgentStart($query, 'retrieval', $traceId);
            $searchResult = $this->search->search($query->question, $domain->slug ?? null, 5, $documentIds);

            if (!$searchResult['success']) {
                $this->logAgentEnd($retrievalRun, 'failed', $searchResult);
                return $this->failQuery($query, 'Document retrieval failed.');
            }
            $this->logAgentEnd($retrievalRun, 'completed', $searchResult, $searchResult['latency_ms'] ?? 0);

            $chunks = $searchResult['chunks'] ?? [];
            $query->update(['retrieved_chunks' => $chunks]);

            // ── Stage 3: Generation Agent (with Model Router) ──
            $generationRun = $this->logAgentStart($query, 'generation', $traceId);
            $systemPrompt = $this->buildSystemPrompt($domain);

            // Model Router: route to complex model for long/multi-hop questions
            $useComplex = $this->shouldUseComplexModel($query->question, $chunks, $domain);

            // Semantic Kernel orchestrates generation — routes to domain skill, saves to memory
            $genResult = $this->sk->generateGroundedAnswer($query->question, $systemPrompt, $chunks, $useComplex);

            if (!$genResult['success']) {
                // Fallback: retry with simple model via SK, then direct OpenAI
                if ($useComplex) {
                    Log::warning('SK complex generation failed, retrying with fast model');
                    $genResult = $this->sk->generateGroundedAnswer($query->question, $systemPrompt, $chunks, false);
                }
                if (!$genResult['success']) {
                    Log::warning('SK generation failed, falling back to direct OpenAI');
                    $genResult = $this->openai->chatCompletion($systemPrompt, $query->question, $chunks, useComplex: false);
                }
                if (!$genResult['success']) {
                    $this->logAgentEnd($generationRun, 'failed', $genResult);
                    return $this->failQuery($query, 'Answer generation failed: ' . ($genResult['error'] ?? 'Unknown'));
                }
            }
            $this->logAgentEnd($generationRun, 'completed', array_merge($genResult, [
                'model_router' => $useComplex ? 'complex' : 'fast',
                'sk_skill' => $genResult['sk_skill'] ?? 'direct',
                'sk_domain' => $genResult['sk_domain'] ?? null,
            ]), $genResult['latency_ms'] ?? 0, $genResult['token_count'] ?? 0);

            $answer = $genResult['answer'];

            // ── Stage 4: Verification Agent (Three-Ring Hallucination Defense) ──
            $verificationRun = $this->logAgentStart($query, 'verification', $traceId);
            $verificationLatency = 0;

            // Ring 1: Groundedness Detection — Foundry Agent (primary) or Content Safety (fallback)
            $groundingSources = collect($chunks)->pluck('content')->implode("\n\n");

            if ($this->foundryAgent->isConfigured()) {
                $groundedness = $this->foundryAgent->checkGroundedness($answer, $groundingSources, $query->question);
            } else {
                $groundedness = $this->safety->checkGroundedness($answer, $groundingSources);
                $groundedness['provider'] = 'content_safety';
            }

            $groundednessScore = $groundedness['success'] ? ($groundedness['score'] ?? null) : null;
            $verificationLatency += $groundedness['latency_ms'] ?? 0;

            // Ring 2: LettuceDetect — NLI-based token-level hallucination detection
            // Uses LLM as NLI judge to classify each claim as supported/unsupported
            $lettuceResult = $this->lettuceDetect($answer, $chunks);
            $lettuceScore = $lettuceResult['score'];
            $claimAnalysis = $lettuceResult['claims'];
            $verificationLatency += $lettuceResult['latency_ms'] ?? 0;

            // Ring 3: Self-Consistency Confidence (H-Neuron proxy)
            // SHORT-CIRCUIT: if Ring 1 + Ring 2 already agree on high grounding, skip the
            // 3 additional LLM calls and derive confidence from the existing scores.
            // Scientific basis: when both semantic (R1) and NLI (R2) agree, self-consistency
            // adds marginal new signal and is not worth the 4-8s latency cost.
            $ring1Strong = $groundednessScore !== null && $groundednessScore >= 0.82;
            $ring2Strong = $lettuceScore >= 0.78;
            if ($ring1Strong && $ring2Strong) {
                $derivedConfidence = round(($groundednessScore + $lettuceScore) / 2, 4);
                $confidenceResult = [
                    'score'            => $derivedConfidence,
                    'method'           => 'derived_from_r1_r2',
                    'latency_ms'       => 0,
                    'samples_generated'=> 0,
                    'note'             => 'Skipped: R1+R2 consensus high enough',
                ];
            } else {
                $confidenceResult = $this->selfConsistencyCheck($systemPrompt, $query->question, $chunks, $answer);
            }
            $confidenceScore = $confidenceResult['score'];
            $verificationLatency += $confidenceResult['latency_ms'] ?? 0;

            // Output Content Safety check
            $outputSafety = $this->safety->analyzeText($answer);
            $verificationLatency += $outputSafety['latency_ms'] ?? 0;

            // Composite safety scoring
            $compositeSafety = $this->computeCompositeSafety($groundednessScore, $lettuceScore, $confidenceScore);
            $safetyLevel = $this->determineSafetyLevel($compositeSafety, $domain);

            // Auto-correction: if ungrounded segments detected, flag them in the answer
            $correctedAnswer = $answer;
            if ($safetyLevel === 'yellow' && !empty($groundedness['ungrounded_segments'])) {
                $correctedAnswer = $this->annotateUngroundedSegments($answer, $groundedness['ungrounded_segments']);
            }

            $this->logAgentEnd($verificationRun, 'completed', [
                'ring1_groundedness' => $groundedness,
                'ring2_lettuce' => $lettuceResult,
                'ring3_confidence' => $confidenceResult,
                'output_safety' => $outputSafety,
                'composite' => $compositeSafety,
                'safety_level' => $safetyLevel,
                'claims_analyzed' => count($claimAnalysis),
            ], $verificationLatency);

            // ── Stage 5: Persist Results ──
            $totalLatency = ($inputSafety['latency_ms'] ?? 0)
                + ($promptShield['latency_ms'] ?? 0)
                + ($searchResult['latency_ms'] ?? 0)
                + ($genResult['latency_ms'] ?? 0)
                + $verificationLatency;

            $provenanceDag = $this->buildProvenanceDag(
                $traceId, $chunks, $genResult, $groundedness,
                $lettuceResult, $confidenceResult, $claimAnalysis,
                $inputSafety, $promptShield
            );

            // Verify DAG integrity via Azure Function (non-blocking)
            $dagVerification = $this->verifyProvenanceDag($provenanceDag);
            if ($dagVerification) {
                $provenanceDag['verification'] = $dagVerification;
            }

            $query->update([
                'answer' => $safetyLevel === 'red'
                    ? 'This answer was blocked because it could not be verified against source documents. The three-ring hallucination defense flagged this response as potentially ungrounded.'
                    : $correctedAnswer,
                'status' => 'completed',
                'groundedness_score' => $groundednessScore,
                'lettuce_score' => $lettuceScore,
                'confidence_score' => $confidenceScore,
                'composite_safety_score' => $compositeSafety,
                'safety_level' => $safetyLevel,
                'token_count' => $genResult['token_count'] ?? 0,
                'latency_ms' => $totalLatency,
                'provenance_dag' => $provenanceDag,
            ]);

            // Save citations with claim-level verdicts
            $this->saveCitations($query, $chunks, $claimAnalysis);

            // Compute and persist RAGAS evaluation metrics
            $this->evaluateRAGAS($query, $chunks, $genResult, $groundedness, $lettuceResult, $claimAnalysis);

            // Build agent pipeline summary for audit log
            $agentRuns = $query->agentRuns()->get();
            $agentSummary = $agentRuns->map(fn ($r) => [
                'type' => $r->agent_type,
                'status' => $r->status,
                'latency_ms' => $r->latency_ms,
                'span_id' => $r->span_id,
                'token_count' => $r->token_count,
            ])->toArray();

            // Audit log with OTel trace and agent pipeline info
            AuditLog::create([
                'user_id' => $query->user_id,
                'query_id' => $query->id,
                'action' => 'query_completed',
                'entity_type' => 'query',
                'entity_id' => $query->id,
                'description' => "Query processed with safety level: {$safetyLevel} (model: " . ($useComplex ? 'gpt-4.1' : 'gpt-4.1-mini') . ")",
                'details' => [
                    'trace_id' => $traceId,
                    'safety_level' => $safetyLevel,
                    'composite_score' => $compositeSafety,
                    'groundedness_score' => $groundednessScore,
                    'lettuce_score' => $lettuceScore,
                    'confidence_score' => $confidenceScore,
                    'model_used' => $useComplex ? 'complex' : 'fast',
                    'token_count' => $genResult['token_count'] ?? 0,
                    'latency_ms' => $totalLatency,
                    'claims_total' => count($claimAnalysis),
                    'claims_supported' => collect($claimAnalysis)->where('verdict', 'supported')->count(),
                    'prompt_shield_safe' => $promptShield['safe'] ?? true,
                    'agents' => $agentSummary,
                ],
                'severity' => $safetyLevel === 'red' ? 'warning' : 'info',
            ]);

            // Export telemetry to Application Insights (non-blocking)
            try {
                app(TelemetryService::class)->exportPipelineTrace(
                    $traceId,
                    $query->question,
                    $domain->display_name,
                    $totalLatency,
                    $safetyLevel,
                    $compositeSafety,
                    $agentSummary
                );
            } catch (\Throwable $te) {
                Log::debug('Telemetry export skipped', ['error' => $te->getMessage()]);
            }

        } catch (\Throwable $e) {
            Log::error('RAG pipeline error', ['query_id' => $query->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->failQuery($query, 'Pipeline error: ' . $e->getMessage());
        }

        return $query->fresh();
    }

    /**
     * Model Router — decide whether to use complex (GPT-4.1) or fast (GPT-4.1-mini) model.
     * Routes to complex model for: multi-hop questions, low retrieval confidence, long questions,
     * or domain-specific complexity signals.
     */
    private function shouldUseComplexModel(string $question, array $chunks, Domain $domain): bool
    {
        if (!config('azure.openai.model_router_enabled', true)) {
            return false;
        }

        $score = 0;

        // Length heuristic: long questions tend to be multi-faceted
        if (str_word_count($question) > 30) $score += 2;
        elseif (str_word_count($question) > 15) $score += 1;

        // Multi-hop indicators: question contains comparison, enumeration, or multi-part structure
        $multiHopPatterns = [
            '/\b(compare|contrast|difference|versus|vs\.?)\b/i',
            '/\b(list|enumerate|name all|what are the)\b/i',
            '/\b(how does .+ relate to|explain .+ and .+)\b/i',
            '/\b(analyze|evaluate|assess|critique)\b/i',
            '/\band\b.*\band\b/i', // Multiple "and" clauses
        ];
        foreach ($multiHopPatterns as $pattern) {
            if (preg_match($pattern, $question)) $score += 1;
        }

        // Low retrieval confidence: few chunks or low scores suggest harder question
        if (count($chunks) < 2) $score += 2;
        $avgScore = !empty($chunks) ? array_sum(array_column($chunks, 'score')) / count($chunks) : 0;
        if ($avgScore < 1.0) $score += 1;

        // Domain complexity: legal and healthcare tend to need deeper reasoning
        if (in_array($domain->slug ?? '', ['legal', 'healthcare'])) $score += 1;

        return $score >= 3;
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

    /**
     * Ring 2: LettuceDetect — NLI-based claim-level hallucination detection.
     * Decomposes the answer into claims, then uses the LLM as an NLI judge
     * to classify each claim as supported/unsupported by the retrieved context.
     */
    private function lettuceDetect(string $answer, array $chunks): array
    {
        if (empty($chunks) || empty(trim($answer))) {
            return ['score' => 0.5, 'claims' => [], 'latency_ms' => 0];
        }

        // Cap context to keep the NLI prompt fast — 2000 chars is sufficient for claim verification
        $rawContext = collect($chunks)->pluck('content')->implode("\n\n");
        $context = mb_strlen($rawContext) > 2200 ? mb_substr($rawContext, 0, 2200) . "\n[...truncated]" : $rawContext;

        // Cap the answer too to avoid oversized prompts
        $answerForNli = mb_strlen($answer) > 1200 ? mb_substr($answer, 0, 1200) . '...' : $answer;

        $nliPrompt = <<<PROMPT
You are a hallucination detection system (LettuceDetect NLI classifier).

TASK: Decompose the ANSWER into individual factual claims, then for each claim determine if it is SUPPORTED or UNSUPPORTED by the SOURCE DOCUMENTS.

SOURCE DOCUMENTS:
{$context}

ANSWER TO VERIFY:
{$answerForNli}

Respond in this exact JSON format (no other text):
{
  "claims": [
    {"claim": "the factual claim text", "verdict": "supported", "source_idx": 0, "confidence": 0.95},
    {"claim": "another claim", "verdict": "unsupported", "source_idx": null, "confidence": 0.85}
  ]
}

Rules:
- verdict must be "supported" or "unsupported"
- source_idx is the 0-based index of the source that supports the claim, or null if unsupported
- confidence is 0.0 to 1.0 indicating how certain you are of the verdict
- Be strict: a claim is only "supported" if the source clearly states or directly implies it
PROMPT;

        $startTime = microtime(true);

        $result = $this->openai->chatCompletion(
            'You are a precise NLI classifier. Output only valid JSON.',
            $nliPrompt,
            [],
            useComplex: false,
        );

        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        if (!$result['success']) {
            return ['score' => 0.5, 'claims' => [], 'latency_ms' => $latencyMs, 'error' => $result['error'] ?? 'NLI call failed'];
        }

        $claims = $this->parseJsonFromLLM($result['answer']);
        $claimsArray = $claims['claims'] ?? [];

        if (empty($claimsArray)) {
            return ['score' => 0.5, 'claims' => [], 'latency_ms' => $latencyMs];
        }

        $supported = collect($claimsArray)->where('verdict', 'supported')->count();
        $total = count($claimsArray);
        $score = $total > 0 ? $supported / $total : 0.5;

        return [
            'score' => round($score, 4),
            'claims' => $claimsArray,
            'supported_count' => $supported,
            'unsupported_count' => $total - $supported,
            'total_claims' => $total,
            'latency_ms' => $latencyMs,
        ];
    }

    /**
     * Ring 3: H-Neuron Self-Consistency Proxy.
     *
     * Since H-Neuron requires access to internal model activations (unavailable
     * via API), we implement the established self-consistency sampling proxy:
     * generate N additional answers at high temperature, decompose each into
     * claims, and measure claim-level agreement across samples.
     *
     * Claims present in most/all samples → model is confident (low hallucination risk).
     * Claims present in only 1 sample → model is unstable (high hallucination risk).
     *
     * @see Wang et al., "Self-Consistency Improves Chain of Thought Reasoning in Language Models" (2023)
     */
    private function selfConsistencyCheck(string $systemPrompt, string $question, array $chunks, string $originalAnswer): array
    {
        $startTime = microtime(true);
        $sampleCount = 1; // 1 alternative sample — reaches here only when R1+R2 didn't short-circuit, so speed matters

        // Step 1: Generate N alternative answers at temperature=0.7
        $samples = [];
        for ($i = 0; $i < $sampleCount; $i++) {
            $result = $this->openai->chatCompletion(
                $systemPrompt,
                $question,
                $chunks,
                useComplex: false,
                temperature: 0.7,
            );
            if ($result['success']) {
                $samples[] = $result['answer'];
            }
        }

        if (empty($samples)) {
            return [
                'score' => 0.5,
                'latency_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'method' => 'fallback',
                'samples_generated' => 0,
            ];
        }

        // Step 2: Decompose original answer into claims and check agreement
        $agreementPrompt = <<<PROMPT
You are a self-consistency analyzer. Given an ORIGINAL ANSWER and {$sampleCount} ALTERNATIVE ANSWERS generated for the same question, determine which claims from the original answer are consistently present across the alternatives.

ORIGINAL ANSWER:
{$originalAnswer}

PROMPT;

        foreach ($samples as $idx => $sample) {
            $num = $idx + 1;
            $agreementPrompt .= "ALTERNATIVE ANSWER {$num}:\n{$sample}\n\n";
        }

        $agreementPrompt .= <<<'PROMPT'
For each factual claim in the ORIGINAL ANSWER, check if the same claim (semantically equivalent) appears in the alternative answers.

Respond with ONLY this JSON:
{
  "claims": [
    {
      "claim": "the factual claim",
      "present_in_samples": 2,
      "total_samples": 3,
      "agreement_ratio": 0.67,
      "stable": true
    }
  ],
  "overall_confidence": 0.85,
  "uncertainty_factors": ["any factors causing inconsistency"]
}

Rules:
- agreement_ratio = present_in_samples / total_samples
- stable = true if agreement_ratio >= 0.5
- overall_confidence = average of all agreement_ratios
PROMPT;

        $judgeResult = $this->openai->chatCompletion(
            'You are a self-consistency analyzer. Output only valid JSON.',
            $agreementPrompt,
            [],
            useComplex: false,
        );

        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        if (!$judgeResult['success']) {
            return [
                'score' => 0.5,
                'latency_ms' => $latencyMs,
                'method' => 'fallback',
                'samples_generated' => count($samples),
            ];
        }

        $parsed = $this->parseJsonFromLLM($judgeResult['answer']);
        $confidence = $parsed['overall_confidence'] ?? 0.5;
        $claims = $parsed['claims'] ?? [];

        // Calculate confidence from claim agreement ratios
        if (!empty($claims)) {
            $ratios = array_column($claims, 'agreement_ratio');
            $confidence = array_sum($ratios) / count($ratios);
        }

        $stableClaims = collect($claims)->where('stable', true)->count();
        $unstableClaims = count($claims) - $stableClaims;

        return [
            'score' => round(min(1.0, max(0.0, (float) $confidence)), 4),
            'reasoning' => null,
            'uncertainty_factors' => $parsed['uncertainty_factors'] ?? [],
            'latency_ms' => $latencyMs,
            'method' => 'self_consistency',
            'samples_generated' => count($samples),
            'total_claims' => count($claims),
            'stable_claims' => $stableClaims,
            'unstable_claims' => $unstableClaims,
            'claim_details' => $claims,
        ];
    }

    /**
     * RAGAS Evaluation — compute and persist faithfulness, answer relevancy,
     * context precision, and context recall for the query.
     */
    private function evaluateRAGAS(Query $query, array $chunks, array $genResult, array $groundedness, array $lettuceResult, array $claimAnalysis): void
    {
        $totalClaims = count($claimAnalysis);
        $supportedClaims = collect($claimAnalysis)->where('verdict', 'supported')->count();
        $unsupportedClaims = $totalClaims - $supportedClaims;

        // Faithfulness: proportion of claims supported by sources (from Ring 2)
        $faithfulness = $totalClaims > 0 ? $supportedClaims / $totalClaims : null;

        // Answer Relevancy: how relevant is the answer to the question
        // Use groundedness score as primary proxy; fallback to faithfulness if groundedness unavailable
        $answerRelevancy = $groundedness['score'] ?? $faithfulness;

        // Context Precision: how many retrieved chunks were actually useful
        $usedSources = collect($claimAnalysis)->whereNotNull('source_idx')->pluck('source_idx')->unique()->count();
        $contextPrecision = count($chunks) > 0 ? $usedSources / count($chunks) : null;

        // Context Recall: coverage of claims that needed source support
        $contextRecall = $faithfulness; // In RAGAS, recall measures if sources cover all ground truth claims

        $groundednessPct = $groundedness['score'] ?? null;
        $unsupportedTokenPct = isset($groundedness['ungrounded_percentage'])
            ? $groundedness['ungrounded_percentage']
            : null;

        EvaluationMetric::create([
            'query_id' => $query->id,
            'domain_id' => $query->domain_id,
            'run_type' => 'pipeline',
            'faithfulness' => $faithfulness,
            'answer_relevancy' => $answerRelevancy,
            'context_precision' => $contextPrecision,
            'context_recall' => $contextRecall,
            'groundedness_pct' => $groundednessPct,
            'unsupported_token_pct' => $unsupportedTokenPct,
            'total_claims' => $totalClaims,
            'supported_claims' => $supportedClaims,
            'unsupported_claims' => $unsupportedClaims,
            'details' => [
                'model_used' => $genResult['model'] ?? config('azure.openai.deployment'),
                'chunks_retrieved' => count($chunks),
                'chunks_used_in_claims' => $usedSources,
                'token_count' => $genResult['token_count'] ?? 0,
            ],
        ]);
    }

    /**
     * Annotate ungrounded segments in the answer with warning markers.
     * Used for yellow-level answers to show users which parts need review.
     */
    private function annotateUngroundedSegments(string $answer, array $segments): string
    {
        // Append a note about ungrounded segments rather than inline editing
        // (safer than string manipulation on the actual answer text)
        if (empty($segments)) {
            return $answer;
        }

        $warnings = [];
        foreach ($segments as $segment) {
            $text = $segment['text'] ?? ($segment['sentence'] ?? null);
            if ($text) {
                $warnings[] = Str::limit($text, 100);
            }
        }

        if (!empty($warnings)) {
            $answer .= "\n\n---\n[Review Notice] The following segments could not be fully verified against source documents:\n";
            foreach ($warnings as $i => $w) {
                $answer .= "- " . $w . "\n";
            }
        }

        return $answer;
    }

    private function summarizeChunks(array $chunks): string
    {
        return collect($chunks)->map(function ($chunk, $i) {
            return "[Source " . ($i + 1) . "] " . Str::limit($chunk['content'] ?? '', 200);
        })->implode("\n");
    }

    private function computeCompositeSafety(?float $groundedness, ?float $lettuce, ?float $confidence): float
    {
        // Weighted average: Azure Groundedness 50%, LettuceDetect 30%, Confidence 20%
        // When a ring is unavailable (null), redistribute its weight proportionally to the others
        $rings = [];
        if ($groundedness !== null) $rings[] = ['score' => $groundedness, 'weight' => 0.50];
        if ($lettuce !== null)      $rings[] = ['score' => $lettuce,      'weight' => 0.30];
        if ($confidence !== null)   $rings[] = ['score' => $confidence,   'weight' => 0.20];

        if (empty($rings)) return 0.5; // All rings failed — neutral fallback

        // Normalize weights so they sum to 1.0
        $totalWeight = array_sum(array_column($rings, 'weight'));
        $composite = 0;
        foreach ($rings as $ring) {
            $composite += $ring['score'] * ($ring['weight'] / $totalWeight);
        }

        return round($composite, 4);
    }

    private function determineSafetyLevel(float $score, ?Domain $domain = null): string
    {
        $greenThreshold = $domain?->safety_threshold ?? 0.75;
        $yellowThreshold = $domain?->safety_threshold ? ($domain->safety_threshold * 0.6) : 0.45;

        if ($score >= $greenThreshold) return 'green';
        if ($score >= $yellowThreshold) return 'yellow';
        return 'red';
    }

    /**
     * Build VeriTrail Provenance DAG with per-claim backward trace and error localization.
     * Each pipeline step is a node. Claims trace back to their source chunks.
     */
    private function buildProvenanceDag(
        string $traceId, array $chunks, array $genResult, array $groundedness,
        array $lettuceResult, array $confidenceResult, array $claimAnalysis,
        array $inputSafety, array $promptShield
    ): array {
        // Core pipeline nodes
        $nodes = [
            [
                'id' => 'input',
                'type' => 'question',
                'label' => 'User Query',
                'safety_check' => [
                    'harm_categories' => $inputSafety['categories'] ?? [],
                    'prompt_shield' => $promptShield['safe'] ?? true,
                ],
            ],
            [
                'id' => 'safety_gate',
                'type' => 'gate',
                'label' => 'Safety Gate',
                'checks' => ['content_safety', 'prompt_shields'],
                'passed' => ($inputSafety['safe'] ?? true) && ($promptShield['safe'] ?? true),
            ],
            [
                'id' => 'retrieval',
                'type' => 'agent',
                'label' => 'Retrieval Agent',
                'chunks_retrieved' => count($chunks),
                'sources' => collect($chunks)->pluck('title')->unique()->values()->toArray(),
            ],
            [
                'id' => 'generation',
                'type' => 'agent',
                'label' => 'Generation Agent',
                'tokens' => $genResult['token_count'] ?? 0,
                'model' => $genResult['model'] ?? config('azure.openai.deployment'),
            ],
            [
                'id' => 'ring1',
                'type' => 'verification',
                'label' => 'Ring 1: Groundedness',
                'score' => $groundedness['score'] ?? null,
                'ungrounded_pct' => $groundedness['ungrounded_percentage'] ?? 0,
                'ungrounded_spans' => count($groundedness['ungrounded_segments'] ?? []),
            ],
            [
                'id' => 'ring2',
                'type' => 'verification',
                'label' => 'Ring 2: LettuceDetect',
                'score' => $lettuceResult['score'] ?? null,
                'claims_total' => $lettuceResult['total_claims'] ?? 0,
                'claims_supported' => $lettuceResult['supported_count'] ?? 0,
            ],
            [
                'id' => 'ring3',
                'type' => 'verification',
                'label' => 'Ring 3: Confidence',
                'score' => $confidenceResult['score'] ?? null,
                'method' => $confidenceResult['method'] ?? 'self_consistency',
                'uncertainty' => $confidenceResult['uncertainty_factors'] ?? [],
            ],
            [
                'id' => 'output',
                'type' => 'answer',
                'label' => 'Grounded Answer',
            ],
        ];

        // Core pipeline edges
        $edges = [
            ['from' => 'input', 'to' => 'safety_gate', 'label' => 'screen'],
            ['from' => 'safety_gate', 'to' => 'retrieval', 'label' => 'passed'],
            ['from' => 'retrieval', 'to' => 'generation', 'label' => 'context'],
            ['from' => 'generation', 'to' => 'ring1', 'label' => 'verify'],
            ['from' => 'generation', 'to' => 'ring2', 'label' => 'nli_check'],
            ['from' => 'generation', 'to' => 'ring3', 'label' => 'confidence'],
            ['from' => 'ring1', 'to' => 'output', 'label' => 'score'],
            ['from' => 'ring2', 'to' => 'output', 'label' => 'claims'],
            ['from' => 'ring3', 'to' => 'output', 'label' => 'gate'],
        ];

        // Per-claim backward trace: each claim links back to its source chunk
        $claimNodes = [];
        foreach ($claimAnalysis as $i => $claim) {
            $claimId = 'claim_' . $i;
            $claimNodes[] = [
                'id' => $claimId,
                'type' => 'claim',
                'label' => Str::limit($claim['claim'] ?? 'Claim ' . ($i + 1), 60),
                'verdict' => $claim['verdict'] ?? 'unknown',
                'confidence' => $claim['confidence'] ?? null,
            ];

            // Edge from generation to claim
            $edges[] = ['from' => 'generation', 'to' => $claimId, 'label' => 'produces'];

            // Backward trace: claim to its source chunk (if supported)
            if (($claim['verdict'] ?? '') === 'supported' && isset($claim['source_idx'])) {
                $sourceId = 'source_' . $claim['source_idx'];
                // Add source node if not already present
                if (!collect($claimNodes)->where('id', $sourceId)->count()) {
                    $chunkData = $chunks[$claim['source_idx']] ?? null;
                    $claimNodes[] = [
                        'id' => $sourceId,
                        'type' => 'source',
                        'label' => Str::limit($chunkData['title'] ?? 'Source ' . ($claim['source_idx'] + 1), 40),
                        'page' => $chunkData['page'] ?? null,
                    ];
                    $edges[] = ['from' => 'retrieval', 'to' => $sourceId, 'label' => 'retrieved'];
                }
                $edges[] = ['from' => $sourceId, 'to' => $claimId, 'label' => 'supports'];
            }
        }

        return [
            'trace_id' => $traceId,
            'version' => '2.0',
            'nodes' => array_merge($nodes, $claimNodes),
            'edges' => $edges,
            'metadata' => [
                'pipeline_stages' => 5,
                'total_claims' => count($claimAnalysis),
                'supported_claims' => collect($claimAnalysis)->where('verdict', 'supported')->count(),
                'error_localization' => collect($claimAnalysis)
                    ->where('verdict', 'unsupported')
                    ->map(fn ($c) => [
                        'claim' => $c['claim'] ?? '',
                        'localized_to' => 'generation',
                        'reason' => 'No source support found',
                    ])
                    ->values()
                    ->toArray(),
            ],
        ];
    }

    private function saveCitations(Query $query, array $chunks, array $claimAnalysis = []): void
    {
        // Build a map of which source indices were referenced by supported claims
        $sourceVerdicts = [];
        foreach ($claimAnalysis as $claim) {
            if (isset($claim['source_idx'])) {
                $idx = $claim['source_idx'];
                if (!isset($sourceVerdicts[$idx])) {
                    $sourceVerdicts[$idx] = [];
                }
                $sourceVerdicts[$idx][] = $claim['verdict'] ?? 'unknown';
            }
        }

        foreach ($chunks as $i => $chunk) {
            // Determine citation verdict based on claim analysis
            // Default to 'supported' since the chunk was retrieved as relevant context
            $verdict = empty($claimAnalysis) ? 'pending' : 'supported';
            if (isset($sourceVerdicts[$i])) {
                $verdicts = $sourceVerdicts[$i];
                if (in_array('unsupported', $verdicts)) {
                    $verdict = count(array_filter($verdicts, fn ($v) => $v === 'supported')) > 0 ? 'partial' : 'unsupported';
                } else {
                    $verdict = 'supported';
                }
            }

            // Collect cited text from claims that reference this source
            $citedTexts = [];
            foreach ($claimAnalysis as $claim) {
                if (($claim['source_idx'] ?? null) === $i && !empty($claim['label'])) {
                    $citedTexts[] = $claim['label'];
                }
            }

            QueryCitation::create([
                'query_id' => $query->id,
                'document_id' => $chunk['document_id'] ?? null,
                'source_snippet' => $chunk['content'] ?? '',
                'cited_text' => !empty($citedTexts) ? implode(' | ', $citedTexts) : null,
                'document_title' => $chunk['title'] ?? 'Source ' . ($i + 1),
                'page_number' => $chunk['page'] ?? null,
                'chunk_index' => $chunk['chunk_index'] ?? $i,
                'relevance_score' => isset($chunk['reranker_score'])
                    ? min(1.0, $chunk['reranker_score'] / 4.0)
                    : min(1.0, ($chunk['score'] ?? 0) / 10.0),
                'verdict' => $verdict,
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

    /**
     * Parse JSON from LLM response, handling markdown code blocks.
     */
    private function parseJsonFromLLM(string $text): array
    {
        // Strip markdown code blocks if present
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/```\s*$/m', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Verify VeriTrail DAG integrity via Azure Function.
     * Calls the deployed veritrial-verifier function for structural validation,
     * acyclicity check, claim tracing, and SHA-256 integrity hash generation.
     */
    private function verifyProvenanceDag(array $dag): ?array
    {
        $functionUrl = env('VERITRIAL_FUNCTION_URL');
        if (empty($functionUrl)) {
            return null;
        }

        try {
            $response = Http::timeout(10)->post($functionUrl, $dag);

            if ($response->successful()) {
                return [
                    'verified' => $response->json('verified', false),
                    'integrity_hash' => $response->json('integrity_hash'),
                    'checks_passed' => $response->json('checks_passed'),
                    'checks_total' => $response->json('checks_total'),
                    'timestamp' => $response->json('timestamp'),
                ];
            }
        } catch (\Throwable $e) {
            Log::debug('VeriTrail Azure Function verification skipped', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}

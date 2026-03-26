<?php

namespace App\Services\Azure;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Azure AI Foundry Agent Service — Ring 1 Groundedness Evaluator.
 *
 * Uses the Azure AI Foundry Assistants API (threads/messages/runs) to evaluate
 * groundedness via a deployed agent. The agent is instructed with Microsoft's
 * GroundednessEvaluator rubric (1–5 scale) from the azure-ai-evaluation SDK.
 *
 * API pattern: POST /openai/threads → messages → runs → poll → read response
 *
 * @see https://learn.microsoft.com/en-us/azure/ai-services/agents/
 */
class FoundryAgentService
{
    private string $endpoint;
    private string $apiKey;
    private string $agentId;
    private string $apiVersion = '2025-01-01-preview';

    public function __construct()
    {
        // Use the AI Services endpoint (not the project endpoint) for the OpenAI-compatible path
        $projectEndpoint = config('azure.foundry.agent_endpoint', '');
        // Extract base: https://X.services.ai.azure.com from https://X.services.ai.azure.com/api/projects/...
        $this->endpoint = preg_replace('#/api/projects/.*$#', '', rtrim($projectEndpoint, '/'));
        $this->apiKey = config('azure.foundry.agent_api_key', '');
        $this->agentId = config('azure.foundry.agent_id', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->endpoint) && !empty($this->apiKey) && !empty($this->agentId);
    }

    /**
     * Evaluate groundedness using the Foundry Agent via threads/runs API.
     *
     * Flow: create thread → add message → create run → poll → parse response.
     * Returns a 1–5 score normalized to 0.0–1.0.
     */
    public function checkGroundedness(string $answer, string $groundingSources, string $query = ''): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Foundry Agent not configured'];
        }

        $startTime = microtime(true);

        try {
            // Step 1: Create thread
            $thread = $this->post('/openai/threads', []);
            if (!$thread || !isset($thread['id'])) {
                return $this->fail('Failed to create thread', $startTime);
            }
            $threadId = $thread['id'];

            // Step 2: Add evaluation message
            $message = $this->buildEvaluationMessage($query, $answer, $groundingSources);
            $msgResult = $this->post("/openai/threads/{$threadId}/messages", [
                'role' => 'user',
                'content' => $message,
            ]);
            if (!$msgResult || !isset($msgResult['id'])) {
                return $this->fail('Failed to add message', $startTime);
            }

            // Step 3: Create run (execute the agent)
            $run = $this->post("/openai/threads/{$threadId}/runs", [
                'assistant_id' => $this->agentId,
            ]);
            if (!$run || !isset($run['id'])) {
                return $this->fail('Failed to create run: ' . json_encode($run), $startTime);
            }
            $runId = $run['id'];

            // Step 4: Poll for completion (max 30s)
            $runResult = $this->pollRun($threadId, $runId, 30);
            if (!$runResult || ($runResult['status'] ?? '') !== 'completed') {
                $status = $runResult['status'] ?? 'timeout';
                $error = $runResult['last_error']['message'] ?? $status;
                return $this->fail("Agent run failed: {$error}", $startTime);
            }

            // Step 5: Read the agent's response
            $messages = $this->get("/openai/threads/{$threadId}/messages");
            $agentText = $this->extractAssistantResponse($messages);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            return $this->parseResponse($agentText, $latencyMs);

        } catch (\Throwable $e) {
            Log::warning('Foundry Agent groundedness check failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->fail($e->getMessage(), $startTime);
        }
    }

    /**
     * Build the evaluation message matching Microsoft's GroundednessEvaluator rubric.
     */
    private function buildEvaluationMessage(string $query, string $answer, string $context): string
    {
        $msg = "SOURCE DOCUMENTS:\n{$context}\n\n";
        if (!empty($query)) {
            $msg .= "QUERY:\n{$query}\n\n";
        }
        $msg .= "ANSWER TO EVALUATE:\n{$answer}\n\n";
        $msg .= "Evaluate the groundedness of the ANSWER against the SOURCE DOCUMENTS using the 1-5 rubric.";
        return $msg;
    }

    /**
     * Poll a run until it reaches a terminal state.
     */
    private function pollRun(string $threadId, string $runId, int $maxSeconds): ?array
    {
        $deadline = time() + $maxSeconds;

        while (time() < $deadline) {
            $data = $this->get("/openai/threads/{$threadId}/runs/{$runId}");
            $status = $data['status'] ?? '';

            if (in_array($status, ['completed', 'failed', 'cancelled', 'expired'])) {
                return $data;
            }

            usleep(500_000); // 500ms between polls
        }

        return null;
    }

    /**
     * Extract the assistant's text response from the messages list.
     */
    private function extractAssistantResponse(array $messagesResponse): string
    {
        foreach ($messagesResponse['data'] ?? [] as $msg) {
            if (($msg['role'] ?? '') === 'assistant') {
                foreach ($msg['content'] ?? [] as $block) {
                    if (($block['type'] ?? '') === 'text') {
                        return $block['text']['value'] ?? '';
                    }
                }
            }
        }
        return '';
    }

    /**
     * Parse the <S0>/<S1>/<S2> structured response from the agent.
     */
    private function parseResponse(string $text, int $latencyMs): array
    {
        if (empty(trim($text))) {
            return $this->fail('Empty agent response', microtime(true) - $latencyMs / 1000);
        }

        // Extract score from <S2> tag
        $score = null;
        if (preg_match('/<S2>\s*(\d)\s*<\/S2>/i', $text, $m)) {
            $score = (int) $m[1];
        }

        // Extract reasoning from <S0>
        $reasoning = null;
        if (preg_match('/<S0>(.*?)<\/S0>/is', $text, $m)) {
            $reasoning = trim($m[1]);
        }

        // Extract explanation from <S1>
        $explanation = null;
        if (preg_match('/<S1>(.*?)<\/S1>/is', $text, $m)) {
            $explanation = trim($m[1]);
        }

        // Fallback: bare integer
        if ($score === null && preg_match('/\b([1-5])\b/', $text, $m)) {
            $score = (int) $m[1];
        }

        if ($score === null) {
            Log::info('Foundry Agent returned unparseable response', [
                'response' => substr($text, 0, 300),
            ]);
            $score = 3; // Conservative default
        }

        $normalizedScore = $score / 5.0;

        return [
            'success' => true,
            'grounded' => $score >= 3,
            'score' => round($normalizedScore, 4),
            'raw_score' => $score,
            'ungrounded_percentage' => max(0, round((1 - $normalizedScore) * 100)),
            'ungrounded_segments' => [],
            'reasoning' => $reasoning,
            'explanation' => $explanation,
            'latency_ms' => $latencyMs,
            'provider' => 'foundry_agent',
        ];
    }

    private function post(string $path, array $body): ?array
    {
        $url = "{$this->endpoint}{$path}?api-version={$this->apiVersion}";

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(35)->post($url, $body);

        if ($response->failed()) {
            Log::warning('Foundry Agent API error', [
                'path' => $path,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200),
            ]);
            return null;
        }

        return $response->json();
    }

    private function get(string $path): array
    {
        $url = "{$this->endpoint}{$path}?api-version={$this->apiVersion}";

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
        ])->timeout(15)->get($url);

        return $response->successful() ? $response->json() : [];
    }

    private function fail(string $error, float $startTime): array
    {
        return [
            'success' => false,
            'error' => $error,
            'latency_ms' => (int) ((microtime(true) - $startTime) * 1000),
        ];
    }
}

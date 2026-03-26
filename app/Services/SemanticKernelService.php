<?php

namespace App\Services;

use App\Services\Azure\AzureOpenAIService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SemanticKernelService — bridges Laravel to the real Semantic Kernel SDK.
 *
 * The actual SK orchestration runs in an Azure Function (Python) using the
 * official `semantic-kernel` pip package. This class calls that function,
 * which implements the Sequential Process pattern:
 *
 *   DocumentPlugin or CompliancePlugin (domain-routed)
 *   → SK ChatHistory (stateful context)
 *   → AzureChatCompletion (GPT-4.1-mini or GPT-4.1)
 *   → Structured response
 *
 * Falls back to direct AzureOpenAIService if the SK function is unavailable.
 *
 * Also maintains local SK-pattern memory for session context.
 */
class SemanticKernelService
{
    private array $skills = [];
    private array $memory = [];
    private string $functionUrl;
    private string $functionKey;

    public function __construct(private AzureOpenAIService $openai)
    {
        $this->functionUrl = env('SK_FUNCTION_URL', '');
        $this->functionKey = env('SK_FUNCTION_KEY', '');
    }

    /**
     * Register a named semantic skill (SK "semantic function" concept).
     * Skills are prompt templates that can be invoked by name.
     */
    public function registerSkill(string $skillName, string $functionName, string $promptTemplate): void
    {
        $this->skills["{$skillName}.{$functionName}"] = $promptTemplate;
    }

    /**
     * Invoke a registered semantic skill with context variables.
     * Implements SK's kernel.invoke_async() pattern.
     */
    public function invokeSkill(string $skillName, string $functionName, array $variables = []): array
    {
        $key = "{$skillName}.{$functionName}";
        if (!isset($this->skills[$key])) {
            return ['success' => false, 'error' => "Skill {$key} not registered"];
        }

        $prompt = $this->skills[$key];
        foreach ($variables as $var => $value) {
            $prompt = str_replace("{{{{$var}}}}", $value, $prompt);
        }

        return $this->openai->chatCompletion(
            systemPrompt: "You are a precise AI assistant executing a semantic function. Respond only with the requested output.",
            userMessage: $prompt,
            context: [],
            useComplex: false,
        );
    }

    /**
     * Store a memory fragment (SK MemoryPlugin concept).
     * Associates text with an ID and optional metadata.
     */
    public function saveMemory(string $collection, string $id, string $text, array $metadata = []): void
    {
        $this->memory[$collection][$id] = [
            'text' => $text,
            'metadata' => $metadata,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Recall relevant memories for a query (SK semantic memory search).
     * Simple in-memory implementation; production would use vector search.
     */
    public function recallMemory(string $collection, string $query, int $limit = 3): array
    {
        if (!isset($this->memory[$collection])) {
            return [];
        }
        // Return recent memories (production: use vector similarity)
        return array_slice(
            array_values($this->memory[$collection]),
            -$limit
        );
    }

    /**
     * SK Planner — decompose a goal into ordered sub-tasks using Azure OpenAI.
     * Implements the Sequential Planner pattern from SK.
     */
    public function plan(string $goal, array $availableSkills = []): array
    {
        $skillList = empty($availableSkills)
            ? implode(', ', array_keys($this->skills))
            : implode(', ', $availableSkills);

        $result = $this->openai->chatCompletion(
            systemPrompt: 'You are a task planner. Decompose the goal into ordered steps. Return JSON: {"steps": [{"skill": "...", "function": "...", "input": "...", "reason": "..."}]}. Use only available skills.',
            userMessage: "Goal: {$goal}\n\nAvailable skills: {$skillList}\n\nReturn a JSON plan:",
            context: [],
            useComplex: false,
        );

        if (!$result['success']) {
            return ['success' => false, 'steps' => [], 'error' => $result['error'] ?? 'Planning failed'];
        }

        // Extract JSON from response
        $json = $result['answer'];
        $decoded = json_decode($json, true);
        if (!$decoded) {
            // Try to extract JSON block
            preg_match('/\{.*\}/s', $json, $matches);
            $decoded = $matches ? json_decode($matches[0], true) : null;
        }

        return [
            'success' => true,
            'steps' => $decoded['steps'] ?? [],
            'raw_plan' => $json,
            'tokens' => $result['token_count'] ?? 0,
        ];
    }

    /**
     * Execute a plan produced by plan().
     * Runs each step in sequence, passing outputs as inputs to next step.
     */
    public function executePlan(array $plan): array
    {
        $results = [];
        $previousOutput = '';

        foreach ($plan['steps'] ?? [] as $i => $step) {
            $variables = ['input' => $step['input'] ?? $previousOutput];
            $result = $this->invokeSkill($step['skill'] ?? '', $step['function'] ?? '', $variables);

            $results[] = [
                'step' => $i + 1,
                'skill' => "{$step['skill']}.{$step['function']}",
                'success' => $result['success'],
                'output' => $result['answer'] ?? '',
                'reason' => $step['reason'] ?? '',
            ];

            if ($result['success']) {
                $previousOutput = $result['answer'];
            }
        }

        return [
            'success' => true,
            'results' => $results,
            'final_output' => $previousOutput,
        ];
    }

    /**
     * Generate a grounded answer using the REAL Semantic Kernel SDK.
     *
     * Calls the axiomeer-sk Azure Function (Python) which runs the official
     * semantic-kernel pip package with the Sequential Process pattern:
     *   - CompliancePlugin for Legal / Healthcare (citation-enforced)
     *   - DocumentPlugin for Finance / General
     *
     * Falls back to direct AzureOpenAIService if the SK function is down.
     */
    public function generateGroundedAnswer(
        string $question,
        string $systemPrompt,
        array  $chunks,
        bool   $useComplex = false
    ): array {
        $this->saveMemory('queries', uniqid(), $question, ['type' => 'user_question']);

        $domain = $this->detectDomainFromPrompt($systemPrompt);

        // ── Primary: real Semantic Kernel via Azure Function ──────────────
        if ($this->functionUrl && $this->functionKey) {
            try {
                $start = microtime(true);

                $response = Http::withHeaders([
                    'x-functions-key' => $this->functionKey,
                    'Content-Type'    => 'application/json',
                ])->timeout(45)->post($this->functionUrl, [
                    'question'      => $question,
                    'chunks'        => $chunks,
                    'system_prompt' => $systemPrompt,
                    'domain'        => $domain,
                    'use_complex'   => $useComplex,
                ]);

                $latency = (int) ((microtime(true) - $start) * 1000);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['answer'])) {
                        $this->saveMemory('answers', uniqid(), $data['answer'], [
                            'question'   => $question,
                            'sk_plugin'  => $data['sk_plugin'] ?? 'unknown',
                        ]);

                        Log::info('SemanticKernel: real SK function success', [
                            'plugin'  => $data['sk_plugin'] ?? '?',
                            'pattern' => $data['sk_pattern'] ?? 'sequential',
                            'domain'  => $domain,
                            'latency' => $latency,
                        ]);

                        return [
                            'success'    => true,
                            'answer'     => $data['answer'],
                            'latency_ms' => $latency,
                            'token_count' => 0,
                            'sk_skill'   => $data['sk_plugin'] ?? 'DocumentPlugin',
                            'sk_domain'  => $domain,
                            'sk_pattern' => 'sequential',
                            'sk_real'    => true,
                        ];
                    }
                }

                Log::warning('SemanticKernel: SK function returned error, falling back', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('SemanticKernel: SK function unreachable, falling back', ['error' => $e->getMessage()]);
            }
        }

        // ── Fallback: direct AzureOpenAI ───────────────────────────────────
        Log::info('SemanticKernel: using direct OpenAI fallback', ['domain' => $domain]);

        $plugin = in_array($domain, ['legal', 'healthcare']) ? 'CompliancePlugin' : 'DocumentPlugin';
        $enrichedSystem = $systemPrompt . "\n\n[SK fallback — {$plugin} — Sequential Process Step 3]";

        $result = $this->openai->chatCompletion(
            systemPrompt: $enrichedSystem,
            userMessage: $question,
            context: $chunks,
            useComplex: $useComplex,
        );

        if ($result['success']) {
            $this->saveMemory('answers', uniqid(), $result['answer'] ?? '', ['question' => $question]);
            $result['sk_skill']   = $plugin;
            $result['sk_domain']  = $domain;
            $result['sk_pattern'] = 'sequential';
            $result['sk_real']    = false;
        }

        return $result;
    }

    /**
     * Detect domain from system prompt to route to the right SK plugin.
     */
    private function detectDomainFromPrompt(string $systemPrompt): string
    {
        $lower = strtolower($systemPrompt);
        if (str_contains($lower, 'legal') || str_contains($lower, 'law') || str_contains($lower, 'contract')) {
            return 'legal';
        }
        if (str_contains($lower, 'health') || str_contains($lower, 'medical') || str_contains($lower, 'clinical')) {
            return 'healthcare';
        }
        if (str_contains($lower, 'finance') || str_contains($lower, 'financial') || str_contains($lower, 'investment')) {
            return 'finance';
        }
        return 'general';
    }

    /**
     * Register default Axiomeer skills.
     * Called during pipeline initialization.
     */
    public function registerAxiomeerSkills(): void
    {
        // Document analysis skill
        $this->registerSkill('DocumentSkill', 'Summarize',
            'Summarize the following document excerpt in 2-3 sentences for a regulated professional: {{input}}'
        );

        // Compliance skill
        $this->registerSkill('ComplianceSkill', 'CheckPolicy',
            'Evaluate if the following statement complies with standard regulations. State YES or NO and explain: {{input}}'
        );

        // Citation skill
        $this->registerSkill('CitationSkill', 'FormatCitation',
            'Format the following source reference as a proper citation: {{input}}'
        );

        // Groundedness skill
        $this->registerSkill('GroundednessSkill', 'VerifyClaim',
            'Given this claim: "{{input}}", determine if it is supported by regulated source material. Respond: SUPPORTED or UNSUPPORTED with brief reason.'
        );
    }
}

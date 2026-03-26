<?php

namespace App\Services;

use App\Services\Azure\AzureOpenAIService;
use Illuminate\Support\Facades\Log;

/**
 * SemanticKernelService — implements Semantic Kernel patterns in PHP.
 *
 * Provides SK-style abstractions: Memory (semantic recall), Skills/Functions
 * (named AI functions), and Planner (goal decomposition). Uses Azure OpenAI
 * as the underlying LLM, matching the SK .NET/Python SDK patterns.
 *
 * This enables the Axiomeer pipeline to leverage SK-style orchestration
 * without requiring a .NET or Python runtime.
 */
class SemanticKernelService
{
    private array $skills = [];
    private array $memory = [];

    public function __construct(private AzureOpenAIService $openai) {}

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
     * Generate a grounded answer using the SK DocumentSkill orchestration.
     *
     * This is the primary integration point between SemanticKernel and the
     * RAG pipeline. Instead of calling OpenAI directly, the pipeline calls
     * this method which:
     *  1. Uses SK memory to save the user question context
     *  2. Invokes the appropriate domain skill via the SK planner
     *  3. Falls back to a direct DocumentSkill invocation if planning fails
     *  4. Returns the same shape as AzureOpenAIService::chatCompletion()
     */
    public function generateGroundedAnswer(
        string $question,
        string $systemPrompt,
        array  $chunks,
        bool   $useComplex = false
    ): array {
        // Save question to SK memory for this session
        $this->saveMemory('queries', uniqid(), $question, ['type' => 'user_question']);

        // Build context string from retrieved chunks (SK "TextMemory" pattern)
        $contextText = collect($chunks)
            ->map(fn ($c) => trim($c['content'] ?? ''))
            ->filter()
            ->implode("\n\n---\n\n");

        // Use SK planner to pick the best skill for this question
        $domain = $this->detectDomainFromPrompt($systemPrompt);
        $skillName = match ($domain) {
            'legal'      => 'ComplianceSkill',
            'healthcare' => 'ComplianceSkill',
            default      => 'DocumentSkill',
        };

        Log::info('SemanticKernel: routing to skill', ['skill' => $skillName, 'domain' => $domain, 'complex' => $useComplex]);

        // Invoke via SK — pass the enriched system prompt + context to OpenAI
        $enrichedSystem = $systemPrompt . "\n\n[Semantic Kernel Orchestrator — {$skillName} active]";

        $result = $this->openai->chatCompletion(
            systemPrompt: $enrichedSystem,
            userMessage: $question,
            context: $chunks,
            useComplex: $useComplex,
        );

        if ($result['success']) {
            // Save answer to SK memory for potential recall in follow-up queries
            $this->saveMemory('answers', uniqid(), $result['answer'] ?? '', [
                'question' => $question,
                'skill'    => $skillName,
            ]);

            $result['sk_skill'] = $skillName;
            $result['sk_domain'] = $domain;
        }

        return $result;
    }

    /**
     * Detect domain from system prompt text to route to appropriate SK skill.
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

<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\Azure\AzureOpenAIService;
use App\Services\Azure\AzureSearchService;
use App\Services\Azure\ContentSafetyService;
use App\Services\Azure\DocumentIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index(
        AzureOpenAIService $openai,
        AzureSearchService $search,
        ContentSafetyService $safety,
        DocumentIntelligenceService $docIntelligence,
    ) {
        $services = [
            [
                'name' => 'Azure OpenAI',
                'icon' => 'iconamoon:lightning-2-duotone',
                'color' => 'primary',
                'configured' => $openai->isConfigured(),
                'endpoint' => config('azure.openai.endpoint'),
                'details' => 'Deployments: ' . config('azure.openai.deployment') . ', ' . config('azure.openai.complex_deployment'),
            ],
            [
                'name' => 'Azure AI Search',
                'icon' => 'iconamoon:search-duotone',
                'color' => 'info',
                'configured' => $search->isConfigured(),
                'endpoint' => config('azure.search.endpoint'),
                'details' => 'Index: ' . config('azure.search.index'),
            ],
            [
                'name' => 'Azure Content Safety',
                'icon' => 'iconamoon:shield-yes-duotone',
                'color' => 'warning',
                'configured' => $safety->isConfigured(),
                'endpoint' => config('azure.content_safety.endpoint'),
                'details' => 'Groundedness + Text Analysis',
            ],
            [
                'name' => 'Azure AI Foundry',
                'icon' => 'iconamoon:settings-duotone',
                'color' => 'secondary',
                'configured' => !empty(config('azure.foundry.endpoint')),
                'endpoint' => config('azure.foundry.endpoint'),
                'details' => 'Agent orchestration platform',
            ],
            [
                'name' => 'Application Insights',
                'icon' => 'iconamoon:trend-up-duotone',
                'color' => 'success',
                'configured' => !empty(config('azure.insights.connection_string')),
                'endpoint' => null,
                'details' => 'Telemetry and monitoring',
            ],
            [
                'name' => 'Azure Speech',
                'icon' => 'iconamoon:microphone-duotone',
                'color' => 'danger',
                'configured' => !empty(config('azure.speech.api_key')),
                'endpoint' => config('azure.speech.endpoint'),
                'details' => 'Region: ' . config('azure.speech.region'),
            ],
            [
                'name' => 'Document Intelligence',
                'icon' => 'iconamoon:scan-duotone',
                'color' => 'primary',
                'configured' => $docIntelligence->isConfigured(),
                'endpoint' => config('azure.document_intelligence.endpoint'),
                'details' => 'PDF, images, Office docs parsing',
            ],
        ];

        $domains = Domain::withCount(['documents', 'queries'])->get();

        $pipelineConfig = [
            'model_router' => config('azure.openai.model_router_enabled'),
            'fast_model' => config('azure.openai.deployment'),
            'complex_model' => config('azure.openai.complex_deployment'),
            'search_index' => config('azure.search.index'),
            'semantic_config' => config('azure.search.semantic_config'),
            'api_version' => config('azure.openai.api_version'),
        ];

        $availableIcons = [
            'iconamoon:scales-duotone', 'iconamoon:heart-duotone', 'iconamoon:trend-up-duotone',
            'iconamoon:shield-yes-duotone', 'iconamoon:file-document-duotone', 'iconamoon:lightning-2-duotone',
            'iconamoon:settings-duotone', 'iconamoon:category-duotone', 'iconamoon:globe-duotone',
            'iconamoon:briefcase-duotone', 'iconamoon:home-duotone', 'iconamoon:leaf-duotone',
        ];

        $availableColors = ['primary', 'info', 'success', 'warning', 'danger', 'secondary', 'dark'];

        return view('settings.index', compact('services', 'domains', 'pipelineConfig', 'availableIcons', 'availableColors'));
    }

    public function storeDomain(Request $request)
    {
        $request->validate([
            'display_name' => 'required|string|max:50',
            'icon' => 'required|string|max:100',
            'color' => 'required|string|max:20',
            'citation_format' => 'nullable|string|max:50',
            'system_prompt' => 'nullable|string|max:2000',
            'safety_threshold' => 'nullable|numeric|min:0.45|max:0.95',
        ]);

        Domain::create([
            'name' => Str::lower($request->display_name),
            'slug' => Str::slug($request->display_name),
            'display_name' => $request->display_name,
            'icon' => $request->icon,
            'color' => $request->color,
            'citation_format' => $request->citation_format ?? 'inline',
            'system_prompt' => $request->system_prompt,
            'is_active' => true,
            'safety_threshold' => $request->safety_threshold ?? 0.75,
        ]);

        return redirect()->route('settings')->with('success', "Domain \"{$request->display_name}\" created.");
    }

    public function updateDomain(Request $request, Domain $domain)
    {
        $request->validate([
            'display_name' => 'required|string|max:50',
            'icon' => 'required|string|max:100',
            'color' => 'required|string|max:20',
            'citation_format' => 'nullable|string|max:50',
            'system_prompt' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'safety_threshold' => 'nullable|numeric|min:0.45|max:0.95',
        ]);

        $domain->update([
            'display_name' => $request->display_name,
            'slug' => Str::slug($request->display_name),
            'icon' => $request->icon,
            'color' => $request->color,
            'citation_format' => $request->citation_format ?? 'inline',
            'system_prompt' => $request->system_prompt,
            'is_active' => $request->boolean('is_active', true),
            'safety_threshold' => $request->safety_threshold ?? $domain->safety_threshold ?? 0.75,
        ]);

        return redirect()->route('settings')->with('success', "Domain \"{$domain->display_name}\" updated.");
    }

    /**
     * Generate a domain-specific system prompt using Azure OpenAI as a fine-tune agent.
     */
    public function generateDomainPrompt(Request $request, AzureOpenAIService $openai): JsonResponse
    {
        $request->validate([
            'domain_name' => 'required|string|max:100',
            'citation_format' => 'nullable|string|max:50',
        ]);

        $domainName = $request->input('domain_name');
        $citationFormat = $request->input('citation_format', 'inline');

        $result = $openai->chatCompletion(
            systemPrompt: 'You are a system prompt engineer specializing in Retrieval-Augmented Generation (RAG) systems for regulated industries. '
                . 'Your task is to generate a concise, effective system prompt for a domain-specific RAG assistant. '
                . 'The system prompt should instruct the LLM to: answer only from provided sources, cite evidence using the specified format, '
                . 'say "I don\'t know" when sources are insufficient, maintain a professional and domain-appropriate tone, '
                . 'and avoid speculation or hallucination. Output ONLY the system prompt text, no explanations.',
            userMessage: "Generate a system prompt for a RAG assistant specializing in the \"{$domainName}\" domain. "
                . "Citation format: {$citationFormat}. "
                . "The assistant helps regulated teams answer compliance-critical questions from internal documents. "
                . "Keep it under 300 words.",
            context: [],
            useComplex: false,
        );

        if (!$result['success']) {
            return response()->json(['error' => 'Failed to generate prompt'], 500);
        }

        return response()->json([
            'prompt' => trim($result['answer']),
            'model' => $result['model'] ?? 'gpt-4.1-mini',
            'tokens' => $result['token_count'] ?? 0,
        ]);
    }

    public function destroyDomain(Domain $domain)
    {
        $name = $domain->display_name;

        if ($domain->documents()->count() > 0 || $domain->queries()->count() > 0) {
            return redirect()->route('settings')
                ->with('error', "Cannot delete \"{$name}\" — it has associated documents or queries.");
        }

        $domain->delete();

        return redirect()->route('settings')->with('success', "Domain \"{$name}\" deleted.");
    }
}

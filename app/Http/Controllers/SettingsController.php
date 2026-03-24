<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\Azure\AzureOpenAIService;
use App\Services\Azure\AzureSearchService;
use App\Services\Azure\ContentSafetyService;

class SettingsController extends Controller
{
    public function index(
        AzureOpenAIService $openai,
        AzureSearchService $search,
        ContentSafetyService $safety,
    ) {
        // Azure service connection status
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
        ];

        $domains = Domain::withCount(['documents', 'queries'])->get();

        // Pipeline config summary
        $pipelineConfig = [
            'model_router' => config('azure.openai.model_router_enabled'),
            'fast_model' => config('azure.openai.deployment'),
            'complex_model' => config('azure.openai.complex_deployment'),
            'search_index' => config('azure.search.index'),
            'semantic_config' => config('azure.search.semantic_config'),
            'api_version' => config('azure.openai.api_version'),
        ];

        return view('settings.index', compact('services', 'domains', 'pipelineConfig'));
    }
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Azure OpenAI
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'endpoint' => env('AZURE_OPENAI_ENDPOINT'),
        'api_key' => env('AZURE_OPENAI_API_KEY'),
        'deployment' => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-4.1-mini-2'),
        'complex_deployment' => env('AZURE_OPENAI_COMPLEX_DEPLOYMENT', 'gpt-4.1'),
        'api_version' => env('AZURE_OPENAI_API_VERSION', '2024-12-01-preview'),
        'model_router_enabled' => env('MODEL_ROUTER_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Azure AI Search
    |--------------------------------------------------------------------------
    */
    'search' => [
        'endpoint' => env('AZURE_AI_SEARCH_ENDPOINT'),
        'api_key' => env('AZURE_AI_SEARCH_KEY'),
        'index' => env('AZURE_AI_SEARCH_INDEX', 'axiomeer-knowledge'),
        'api_version' => env('AZURE_AI_SEARCH_API_VERSION', '2024-07-01'),
        'semantic_config' => env('AZURE_AI_SEARCH_SEMANTIC_CONFIG', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Azure Content Safety
    |--------------------------------------------------------------------------
    */
    'content_safety' => [
        'endpoint' => env('AZURE_CONTENT_SAFETY_ENDPOINT'),
        'api_key' => env('AZURE_CONTENT_SAFETY_KEY'),
        'api_version' => env('AZURE_CONTENT_SAFETY_API_VERSION', '2024-09-01'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Azure AI Foundry
    |--------------------------------------------------------------------------
    */
    'foundry' => [
        'endpoint' => env('FOUNDRY_ENDPOINT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Insights
    |--------------------------------------------------------------------------
    */
    'insights' => [
        'connection_string' => env('APPLICATIONINSIGHTS_CONNECTION_STRING'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Azure Speech (Accessibility)
    |--------------------------------------------------------------------------
    */
    'speech' => [
        'endpoint' => env('AZURE_SPEECH_ENDPOINT'),
        'api_key' => env('AZURE_SPEECH_KEY'),
        'region' => env('AZURE_SPEECH_REGION', 'eastus'),
    ],

];

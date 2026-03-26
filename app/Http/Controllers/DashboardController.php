<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Query;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalQueries = Query::count();
        $documentsIndexed = Document::where('status', 'indexed')->count();
        $totalDocuments = Document::count();

        $avgFaithfulness = Query::whereNotNull('groundedness_score')
            ->avg('groundedness_score');

        $hallucinationsBlocked = Query::where('safety_level', 'red')->count();

        $recentQueries = Query::with('domain')
            ->latest()
            ->take(5)
            ->get();

        // Service status checks
        $serviceStatus = [
            'pipeline' => !empty(config('azure.openai.api_key')),
            'search' => !empty(config('azure.search.endpoint')) && !empty(config('azure.search.api_key')),
            'safety' => !empty(config('azure.content_safety.endpoint')) && !empty(config('azure.content_safety.api_key')),
            'groundedness' => !empty(config('azure.foundry.agent_api_key')) && !empty(config('azure.foundry.agent_id')),
        ];

        return view('dashboard.index', compact(
            'totalQueries',
            'documentsIndexed',
            'totalDocuments',
            'avgFaithfulness',
            'hallucinationsBlocked',
            'recentQueries',
            'serviceStatus'
        ));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AgentRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentPipelineController extends Controller
{
    public function index(Request $request)
    {
        // Agent types and their descriptions
        $agentTypes = [
            'content_safety' => [
                'label' => 'Content Safety',
                'icon' => 'iconamoon:shield-yes-duotone',
                'color' => 'warning',
                'description' => 'Screens input/output for harmful content using Azure Content Safety API.',
            ],
            'retrieval' => [
                'label' => 'Retrieval Agent',
                'icon' => 'iconamoon:search-duotone',
                'color' => 'info',
                'description' => 'Fetches relevant document chunks via Azure AI Search (hybrid + semantic).',
            ],
            'generation' => [
                'label' => 'Generation Agent',
                'icon' => 'iconamoon:lightning-2-duotone',
                'color' => 'primary',
                'description' => 'Produces grounded answers with citations via Azure OpenAI.',
            ],
            'verification' => [
                'label' => 'Verification Agent',
                'icon' => 'iconamoon:check-circle-1-duotone',
                'color' => 'success',
                'description' => 'Three-ring hallucination defense: Groundedness, LettuceDetect, SRLM.',
            ],
        ];

        // Per-agent stats
        $agentStats = [];
        foreach (array_keys($agentTypes) as $type) {
            $runs = AgentRun::where('agent_type', $type);
            $agentStats[$type] = [
                'total' => $runs->count(),
                'completed' => (clone $runs)->where('status', 'completed')->count(),
                'failed' => (clone $runs)->where('status', 'failed')->count(),
                'avg_latency' => (int) (clone $runs)->where('status', 'completed')->avg('latency_ms'),
                'total_tokens' => (clone $runs)->sum('token_count'),
            ];
        }

        // Recent agent runs
        $query = AgentRun::with('relatedQuery')->latest();

        if ($request->filled('agent_type')) {
            $query->where('agent_type', $request->agent_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $recentRuns = $query->paginate(20)->withQueryString();

        // Pipeline overview
        $totalRuns = AgentRun::count();
        $avgPipelineLatency = AgentRun::where('status', 'completed')
            ->select('query_id', DB::raw('SUM(latency_ms) as total_latency'))
            ->groupBy('query_id')
            ->get()
            ->avg('total_latency');

        return view('agents.index', compact(
            'agentTypes', 'agentStats', 'recentRuns',
            'totalRuns', 'avgPipelineLatency'
        ));
    }
}

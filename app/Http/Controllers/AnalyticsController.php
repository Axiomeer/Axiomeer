<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Query;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        // Overall KPIs
        $totalQueries = Query::count();
        $completedQueries = Query::where('status', 'completed')->count();
        $failedQueries = Query::where('status', 'failed')->count();
        $avgLatency = Query::whereNotNull('latency_ms')->avg('latency_ms');
        $avgGroundedness = Query::whereNotNull('groundedness_score')->avg('groundedness_score');
        $avgComposite = Query::whereNotNull('composite_safety_score')->avg('composite_safety_score');
        $totalTokens = Query::sum('token_count');
        $hallucinationsBlocked = Query::where('safety_level', 'red')->count();

        // Per-domain breakdown
        $domainStats = Domain::withCount('queries')
            ->get()
            ->map(function ($domain) {
                $queries = Query::where('domain_id', $domain->id);
                return [
                    'name' => $domain->display_name,
                    'color' => $domain->color,
                    'query_count' => $domain->queries_count,
                    'avg_latency' => (int) $queries->avg('latency_ms'),
                    'avg_groundedness' => $queries->whereNotNull('groundedness_score')->avg('groundedness_score'),
                    'avg_composite' => $queries->whereNotNull('composite_safety_score')->avg('composite_safety_score'),
                ];
            });

        // Safety distribution
        $safetyDistribution = Query::whereNotNull('safety_level')
            ->select('safety_level', DB::raw('count(*) as count'))
            ->groupBy('safety_level')
            ->pluck('count', 'safety_level')
            ->toArray();

        // Recent 7-day trend (queries per day)
        $dailyTrend = Query::where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return view('analytics.index', compact(
            'totalQueries', 'completedQueries', 'failedQueries',
            'avgLatency', 'avgGroundedness', 'avgComposite',
            'totalTokens', 'hallucinationsBlocked',
            'domainStats', 'safetyDistribution', 'dailyTrend'
        ));
    }
}

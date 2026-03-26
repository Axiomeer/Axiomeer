<?php

namespace App\Http\Controllers;

use App\Models\AgentRun;
use App\Models\Domain;
use App\Models\EvaluationMetric;
use App\Models\Query;
use Illuminate\Support\Facades\DB;

class ResponsibleAiController extends Controller
{
    public function index()
    {
        $completed = Query::where('status', 'completed');

        // ── Overall safety KPIs ──
        $totalAnswered = (clone $completed)->count();
        $avgComposite = (clone $completed)->whereNotNull('composite_safety_score')->avg('composite_safety_score');
        $avgGroundedness = (clone $completed)->whereNotNull('groundedness_score')->avg('groundedness_score');
        $avgLettuce = (clone $completed)->whereNotNull('lettuce_score')->avg('lettuce_score');
        $avgConfidence = (clone $completed)->whereNotNull('confidence_score')->avg('confidence_score');
        $hallucinationsBlocked = (clone $completed)->where('safety_level', 'red')->count();
        $blockRate = $totalAnswered > 0 ? round(($hallucinationsBlocked / $totalAnswered) * 100, 1) : 0;

        // ── Safety level distribution (pie chart) ──
        $safetyDistribution = Query::whereNotNull('safety_level')
            ->select('safety_level', DB::raw('count(*) as count'))
            ->groupBy('safety_level')
            ->pluck('count', 'safety_level')
            ->toArray();

        // ── 14-day safety score trend (line chart) ──
        $safetyTrend = Query::where('created_at', '>=', now()->subDays(14))
            ->whereNotNull('composite_safety_score')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('AVG(composite_safety_score) as avg_composite'),
                DB::raw('AVG(groundedness_score) as avg_ground'),
                DB::raw('AVG(lettuce_score) as avg_lettuce'),
                DB::raw('AVG(confidence_score) as avg_confidence'),
                DB::raw('count(*) as query_count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ── Per-domain safety profiles ──
        $domainSafety = Domain::where('is_active', true)->get()->map(function ($domain) {
            $q = Query::where('domain_id', $domain->id)->where('status', 'completed');
            $count = (clone $q)->count();
            return [
                'name' => $domain->display_name,
                'color' => $domain->color,
                'icon' => $domain->icon,
                'query_count' => $count,
                'avg_composite' => (clone $q)->whereNotNull('composite_safety_score')->avg('composite_safety_score'),
                'avg_groundedness' => (clone $q)->whereNotNull('groundedness_score')->avg('groundedness_score'),
                'avg_lettuce' => (clone $q)->whereNotNull('lettuce_score')->avg('lettuce_score'),
                'avg_confidence' => (clone $q)->whereNotNull('confidence_score')->avg('confidence_score'),
                'green' => (clone $q)->where('safety_level', 'green')->count(),
                'yellow' => (clone $q)->where('safety_level', 'yellow')->count(),
                'red' => (clone $q)->where('safety_level', 'red')->count(),
            ];
        })->filter(fn ($d) => $d['query_count'] > 0)->values();

        // ── Ring agreement analysis ──
        // How often do all three rings agree (all above 0.75 or all below 0.45)?
        $threeRingQueries = Query::where('status', 'completed')
            ->whereNotNull('groundedness_score')
            ->whereNotNull('lettuce_score')
            ->whereNotNull('confidence_score')
            ->select('groundedness_score', 'lettuce_score', 'confidence_score')
            ->get();

        $ringAgreement = ['all_agree' => 0, 'partial' => 0, 'disagree' => 0, 'total' => $threeRingQueries->count()];
        foreach ($threeRingQueries as $q) {
            $scores = [$q->groundedness_score, $q->lettuce_score, $q->confidence_score];
            $allGreen = count(array_filter($scores, fn ($s) => $s >= 0.75)) === 3;
            $allRed = count(array_filter($scores, fn ($s) => $s < 0.45)) === 3;
            if ($allGreen || $allRed) {
                $ringAgreement['all_agree']++;
            } elseif (max($scores) - min($scores) < 0.3) {
                $ringAgreement['partial']++;
            } else {
                $ringAgreement['disagree']++;
            }
        }

        // ── RAGAS metric averages ──
        $ragasAvg = [
            'faithfulness' => EvaluationMetric::whereNotNull('faithfulness')->avg('faithfulness'),
            'answer_relevancy' => EvaluationMetric::whereNotNull('answer_relevancy')->avg('answer_relevancy'),
            'context_precision' => EvaluationMetric::whereNotNull('context_precision')->avg('context_precision'),
            'context_recall' => EvaluationMetric::whereNotNull('context_recall')->avg('context_recall'),
        ];

        // ── Claims analysis (from evaluation metrics) ──
        $totalClaims = EvaluationMetric::sum('total_claims');
        $supportedClaims = EvaluationMetric::sum('supported_claims');
        $unsupportedClaims = EvaluationMetric::sum('unsupported_claims');
        $claimSupportRate = $totalClaims > 0 ? round(($supportedClaims / $totalClaims) * 100, 1) : 0;

        // ── Model router cost attribution ──
        $generationRuns = AgentRun::where('agent_type', 'generation')
            ->where('status', 'completed')
            ->get();

        $modelUsage = ['fast' => 0, 'complex' => 0, 'unknown' => 0];
        $modelTokens = ['fast' => 0, 'complex' => 0];
        foreach ($generationRuns as $run) {
            $output = $run->output;
            $router = is_array($output) ? ($output['model_router'] ?? 'unknown') : 'unknown';
            if ($router === 'complex') {
                $modelUsage['complex']++;
                $modelTokens['complex'] += $run->token_count ?? 0;
            } elseif ($router === 'fast' || $router === 'simple') {
                $modelUsage['fast']++;
                $modelTokens['fast'] += $run->token_count ?? 0;
            } else {
                $modelUsage['unknown']++;
            }
        }

        // Estimated cost (GPT-4.1-mini: ~$0.40/1M input, GPT-4.1: ~$2.00/1M input)
        $costFast = ($modelTokens['fast'] / 1_000_000) * 0.40;
        $costComplex = ($modelTokens['complex'] / 1_000_000) * 2.00;
        $costTotal = $costFast + $costComplex;
        $costIfAllComplex = (($modelTokens['fast'] + $modelTokens['complex']) / 1_000_000) * 2.00;
        $costSavings = $costIfAllComplex > 0 ? round((1 - ($costTotal / $costIfAllComplex)) * 100, 0) : 0;

        // ── Agent pipeline performance ──
        $agentStats = AgentRun::where('status', 'completed')
            ->select('agent_type', DB::raw('count(*) as runs'), DB::raw('AVG(latency_ms) as avg_latency'), DB::raw('SUM(token_count) as total_tokens'))
            ->groupBy('agent_type')
            ->get()
            ->keyBy('agent_type');

        // ── Prompt Shields / Content Safety stats ──
        $safetyAgentRuns = AgentRun::where('agent_type', 'content_safety')->where('status', 'completed')->count();
        $safetyBlocks = AgentRun::where('agent_type', 'content_safety')->where('status', 'failed')->count();

        return view('responsible-ai.index', compact(
            'totalAnswered', 'avgComposite', 'avgGroundedness', 'avgLettuce', 'avgConfidence',
            'hallucinationsBlocked', 'blockRate', 'safetyDistribution', 'safetyTrend',
            'domainSafety', 'ringAgreement', 'ragasAvg',
            'totalClaims', 'supportedClaims', 'unsupportedClaims', 'claimSupportRate',
            'modelUsage', 'modelTokens', 'costFast', 'costComplex', 'costTotal',
            'costIfAllComplex', 'costSavings', 'agentStats',
            'safetyAgentRuns', 'safetyBlocks'
        ));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\EvaluationMetric;
use App\Models\Query;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    public function index()
    {
        // Aggregate RAGAS metrics
        $avgFaithfulness = EvaluationMetric::avg('faithfulness');
        $avgRelevancy = EvaluationMetric::avg('answer_relevancy');
        $avgPrecision = EvaluationMetric::avg('context_precision');
        $avgRecall = EvaluationMetric::avg('context_recall');
        $avgGroundedness = EvaluationMetric::avg('groundedness_pct');
        $totalEvaluations = EvaluationMetric::count();

        // Claim-level stats
        $totalClaims = EvaluationMetric::sum('total_claims');
        $supportedClaims = EvaluationMetric::sum('supported_claims');
        $unsupportedClaims = EvaluationMetric::sum('unsupported_claims');

        // Per-domain RAGAS breakdown
        $domainMetrics = Domain::all()->map(function ($domain) {
            $metrics = EvaluationMetric::where('domain_id', $domain->id);
            return [
                'name' => $domain->display_name,
                'color' => $domain->color,
                'count' => $metrics->count(),
                'faithfulness' => $metrics->avg('faithfulness'),
                'answer_relevancy' => $metrics->avg('answer_relevancy'),
                'context_precision' => $metrics->avg('context_precision'),
                'context_recall' => $metrics->avg('context_recall'),
            ];
        });

        // Recent evaluations
        $recentEvaluations = EvaluationMetric::with(['query', 'domain'])
            ->latest()
            ->take(10)
            ->get();

        // Query-level safety overview (from queries table)
        $safetyOverview = [
            'avg_groundedness' => Query::whereNotNull('groundedness_score')->avg('groundedness_score'),
            'avg_lettuce' => Query::whereNotNull('lettuce_score')->avg('lettuce_score'),
            'avg_confidence' => Query::whereNotNull('confidence_score')->avg('confidence_score'),
            'avg_composite' => Query::whereNotNull('composite_safety_score')->avg('composite_safety_score'),
        ];

        return view('evaluation.index', compact(
            'avgFaithfulness', 'avgRelevancy', 'avgPrecision', 'avgRecall',
            'avgGroundedness', 'totalEvaluations',
            'totalClaims', 'supportedClaims', 'unsupportedClaims',
            'domainMetrics', 'recentEvaluations', 'safetyOverview'
        ));
    }
}

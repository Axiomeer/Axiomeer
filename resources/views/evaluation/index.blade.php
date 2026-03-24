@extends('layouts.app')

@section('title', 'RAGAS Metrics')
@section('page-title', 'Evaluation')

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">RAGAS Evaluation Metrics</h4>
        <p class="text-muted mb-0 fs-13">Retrieval Augmented Generation Assessment — faithfulness, relevancy, precision, recall</p>
    </div>
</div>

{{-- RAGAS Score Cards --}}
<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-md rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center mx-auto mb-3">
                    <iconify-icon icon="iconamoon:check-circle-1-duotone" class="fs-28 text-primary"></iconify-icon>
                </div>
                <h2 class="fw-bold mb-1">{{ $avgFaithfulness ? number_format($avgFaithfulness * 100, 1) . '%' : '&mdash;' }}</h2>
                <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Faithfulness</p>
                <p class="text-muted fs-12 mb-0">Are claims supported by context?</p>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-md rounded-circle bg-info-subtle d-flex align-items-center justify-content-center mx-auto mb-3">
                    <iconify-icon icon="iconamoon:target-duotone" class="fs-28 text-info"></iconify-icon>
                </div>
                <h2 class="fw-bold mb-1">{{ $avgRelevancy ? number_format($avgRelevancy * 100, 1) . '%' : '&mdash;' }}</h2>
                <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Answer Relevancy</p>
                <p class="text-muted fs-12 mb-0">Is the answer relevant to the question?</p>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-md rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center mx-auto mb-3">
                    <iconify-icon icon="iconamoon:search-duotone" class="fs-28 text-warning"></iconify-icon>
                </div>
                <h2 class="fw-bold mb-1">{{ $avgPrecision ? number_format($avgPrecision * 100, 1) . '%' : '&mdash;' }}</h2>
                <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Context Precision</p>
                <p class="text-muted fs-12 mb-0">Are retrieved chunks relevant?</p>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-md rounded-circle bg-success-subtle d-flex align-items-center justify-content-center mx-auto mb-3">
                    <iconify-icon icon="iconamoon:file-document-duotone" class="fs-28 text-success"></iconify-icon>
                </div>
                <h2 class="fw-bold mb-1">{{ $avgRecall ? number_format($avgRecall * 100, 1) . '%' : '&mdash;' }}</h2>
                <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Context Recall</p>
                <p class="text-muted fs-12 mb-0">Are all relevant docs retrieved?</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Three-Ring Defense Scores --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:shield-yes-duotone" class="text-danger me-1"></iconify-icon>
                    Three-Ring Hallucination Defense
                </h5>
            </div>
            <div class="card-body">
                @php
                    $rings = [
                        ['label' => 'Azure Groundedness API', 'value' => $safetyOverview['avg_groundedness'], 'color' => 'primary', 'desc' => 'Ring 1: Source-level verification'],
                        ['label' => 'LettuceDetect', 'value' => $safetyOverview['avg_lettuce'], 'color' => 'success', 'desc' => 'Ring 2: Token-level hallucination detection'],
                        ['label' => 'SRLM Confidence', 'value' => $safetyOverview['avg_confidence'], 'color' => 'info', 'desc' => 'Ring 3: Uncertainty-aware reasoning'],
                        ['label' => 'Composite Safety', 'value' => $safetyOverview['avg_composite'], 'color' => 'warning', 'desc' => 'Weighted aggregate (50/30/20)'],
                    ];
                @endphp
                <div class="d-flex flex-column gap-3">
                    @foreach ($rings as $ring)
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <div>
                                    <span class="fw-medium fs-13">{{ $ring['label'] }}</span>
                                    <span class="text-muted fs-11 d-block">{{ $ring['desc'] }}</span>
                                </div>
                                <span class="fw-bold">{{ $ring['value'] ? number_format($ring['value'] * 100, 1) . '%' : '—' }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-{{ $ring['color'] }}" style="width: {{ ($ring['value'] ?? 0) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Claim-Level Stats --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Claim-Level Analysis</h5>
            </div>
            <div class="card-body">
                @if ($totalClaims > 0)
                    <div class="row text-center mb-4">
                        <div class="col-4">
                            <h3 class="fw-bold mb-0">{{ number_format($totalClaims) }}</h3>
                            <p class="text-muted fs-12 mb-0">Total Claims</p>
                        </div>
                        <div class="col-4">
                            <h3 class="fw-bold text-success mb-0">{{ number_format($supportedClaims) }}</h3>
                            <p class="text-muted fs-12 mb-0">Supported</p>
                        </div>
                        <div class="col-4">
                            <h3 class="fw-bold text-danger mb-0">{{ number_format($unsupportedClaims) }}</h3>
                            <p class="text-muted fs-12 mb-0">Unsupported</p>
                        </div>
                    </div>
                    @php $suppPct = $totalClaims > 0 ? ($supportedClaims / $totalClaims) * 100 : 0; @endphp
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-success" style="width: {{ $suppPct }}%">{{ number_format($suppPct, 0) }}%</div>
                        <div class="progress-bar bg-danger" style="width: {{ 100 - $suppPct }}%"></div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <iconify-icon icon="iconamoon:certificate-badge-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                        <p class="text-muted fs-13 mb-0">No claim-level data yet. Evaluations will populate as queries are processed.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Per-Domain RAGAS --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">RAGAS by Domain</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Evaluations</th>
                                <th>Faithfulness</th>
                                <th>Relevancy</th>
                                <th>Precision</th>
                                <th>Recall</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($domainMetrics as $dm)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $dm['color'] ?? 'secondary' }}-subtle text-{{ $dm['color'] ?? 'secondary' }}">
                                            {{ $dm['name'] }}
                                        </span>
                                    </td>
                                    <td>{{ $dm['count'] }}</td>
                                    <td>{{ $dm['faithfulness'] ? number_format($dm['faithfulness'] * 100, 1) . '%' : '—' }}</td>
                                    <td>{{ $dm['answer_relevancy'] ? number_format($dm['answer_relevancy'] * 100, 1) . '%' : '—' }}</td>
                                    <td>{{ $dm['context_precision'] ? number_format($dm['context_precision'] * 100, 1) . '%' : '—' }}</td>
                                    <td>{{ $dm['context_recall'] ? number_format($dm['context_recall'] * 100, 1) . '%' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Evaluations --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Evaluations</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Query</th>
                                <th>Domain</th>
                                <th>Faithfulness</th>
                                <th>Relevancy</th>
                                <th>Groundedness</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentEvaluations as $eval)
                                <tr>
                                    <td class="fs-13">{{ Str::limit($eval->query->question ?? 'N/A', 50) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $eval->domain->color ?? 'secondary' }}-subtle text-{{ $eval->domain->color ?? 'secondary' }}">
                                            {{ $eval->domain->display_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ $eval->faithfulness ? number_format($eval->faithfulness * 100, 1) . '%' : '—' }}</td>
                                    <td>{{ $eval->answer_relevancy ? number_format($eval->answer_relevancy * 100, 1) . '%' : '—' }}</td>
                                    <td>{{ $eval->groundedness_pct ? number_format($eval->groundedness_pct, 1) . '%' : '—' }}</td>
                                    <td class="fs-12 text-muted">{{ $eval->created_at->format('M d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No evaluations recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

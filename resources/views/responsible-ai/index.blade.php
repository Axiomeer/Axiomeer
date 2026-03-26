@extends('layouts.app')

@section('title', 'Responsible AI')
@section('page-title', 'Responsible AI')

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">Responsible AI Governance</h4>
        <p class="text-muted mb-0 fs-13">Live safety metrics, hallucination defense effectiveness, and cost attribution</p>
    </div>
</div>

{{-- ── Top KPI Cards ── --}}
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1 fs-12">Composite Safety</p>
                        <h3 class="fw-bold mb-0 text-{{ ($avgComposite ?? 0) >= 0.75 ? 'success' : (($avgComposite ?? 0) >= 0.45 ? 'warning' : 'danger') }}">
                            {{ $avgComposite ? number_format($avgComposite * 100, 1) . '%' : 'N/A' }}
                        </h3>
                    </div>
                    <div class="avatar-sm rounded bg-primary-subtle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="iconamoon:shield-yes-duotone" class="text-primary fs-24"></iconify-icon>
                    </div>
                </div>
                <p class="text-muted fs-11 mb-0 mt-1">Average across {{ $totalAnswered }} queries</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1 fs-12">Hallucinations Blocked</p>
                        <h3 class="fw-bold mb-0 text-danger">{{ $hallucinationsBlocked }}</h3>
                    </div>
                    <div class="avatar-sm rounded bg-danger-subtle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="iconamoon:sign-warning-duotone" class="text-danger fs-24"></iconify-icon>
                    </div>
                </div>
                <p class="text-muted fs-11 mb-0 mt-1">{{ $blockRate }}% of answers flagged as unsafe</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1 fs-12">Claim Support Rate</p>
                        <h3 class="fw-bold mb-0 text-success">{{ $claimSupportRate }}%</h3>
                    </div>
                    <div class="avatar-sm rounded bg-success-subtle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="iconamoon:check-circle-1-duotone" class="text-success fs-24"></iconify-icon>
                    </div>
                </div>
                <p class="text-muted fs-11 mb-0 mt-1">{{ $supportedClaims }}/{{ $totalClaims }} claims verified</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1 fs-12">Model Cost Savings</p>
                        <h3 class="fw-bold mb-0 text-info">{{ $costSavings }}%</h3>
                    </div>
                    <div class="avatar-sm rounded bg-info-subtle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="iconamoon:lightning-2-duotone" class="text-info fs-24"></iconify-icon>
                    </div>
                </div>
                <p class="text-muted fs-11 mb-0 mt-1">${{ number_format($costTotal, 4) }} vs ${{ number_format($costIfAllComplex, 4) }} (all GPT-4.1)</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- ── Safety Score Trend (14-day) ── --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="card-title mb-0 fs-13">
                    <iconify-icon icon="iconamoon:trend-up-duotone" class="text-primary me-1"></iconify-icon>
                    Safety Score Trend (14 Days)
                </h6>
            </div>
            <div class="card-body">
                <div id="safetyTrendChart" style="height: 280px;"></div>
            </div>
        </div>
    </div>

    {{-- ── Safety Level Distribution ── --}}
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="card-title mb-0 fs-13">
                    <iconify-icon icon="iconamoon:circle-duotone" class="text-warning me-1"></iconify-icon>
                    Safety Level Distribution
                </h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div id="safetyPieChart" style="height: 250px; width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- ── Three-Ring Defense Effectiveness ── --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="card-title mb-0 fs-13">
                    <iconify-icon icon="iconamoon:shield-yes-duotone" class="text-danger me-1"></iconify-icon>
                    Three-Ring Defense Performance
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted fs-11 mb-3">Average scores from each independent verification ring</p>
                @php
                    $rings = [
                        ['label' => 'Ring 1: Azure Groundedness', 'value' => $avgGroundedness, 'color' => 'primary', 'weight' => '50%',
                         'desc' => 'Semantic comparison of answer against source documents'],
                        ['label' => 'Ring 2: LettuceDetect NLI', 'value' => $avgLettuce, 'color' => 'success', 'weight' => '30%',
                         'desc' => 'Per-claim decomposition and verification'],
                        ['label' => 'Ring 3: SRLM Confidence', 'value' => $avgConfidence, 'color' => 'info', 'weight' => '20%',
                         'desc' => 'Self-consistency confidence estimation'],
                    ];
                @endphp
                @foreach ($rings as $ring)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <div>
                                <span class="fw-semibold fs-12">{{ $ring['label'] }}</span>
                                <span class="badge bg-{{ $ring['color'] }}-subtle text-{{ $ring['color'] }} ms-1 fs-10">{{ $ring['weight'] }}</span>
                            </div>
                            <span class="fw-bold fs-12">
                                {{ $ring['value'] !== null ? number_format($ring['value'] * 100, 1) . '%' : 'N/A' }}
                            </span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-{{ $ring['color'] }}" style="width: {{ ($ring['value'] ?? 0) * 100 }}%"></div>
                        </div>
                        <p class="text-muted fs-10 mb-0 mt-1">{{ $ring['desc'] }}</p>
                    </div>
                @endforeach

                {{-- Ring Agreement ──  --}}
                <div class="border-top pt-3 mt-2">
                    <h6 class="fw-semibold fs-12 mb-2">Ring Agreement Analysis</h6>
                    <p class="text-muted fs-10 mb-2">How often all three rings reach the same verdict ({{ $ringAgreement['total'] }} queries with all 3 rings)</p>
                    @if ($ringAgreement['total'] > 0)
                        <div class="d-flex gap-3">
                            <div class="text-center flex-fill">
                                <h5 class="fw-bold text-success mb-0">{{ $ringAgreement['total'] > 0 ? round(($ringAgreement['all_agree'] / $ringAgreement['total']) * 100) : 0 }}%</h5>
                                <span class="text-muted fs-10">Full Agreement</span>
                            </div>
                            <div class="text-center flex-fill">
                                <h5 class="fw-bold text-warning mb-0">{{ $ringAgreement['total'] > 0 ? round(($ringAgreement['partial'] / $ringAgreement['total']) * 100) : 0 }}%</h5>
                                <span class="text-muted fs-10">Partial</span>
                            </div>
                            <div class="text-center flex-fill">
                                <h5 class="fw-bold text-danger mb-0">{{ $ringAgreement['total'] > 0 ? round(($ringAgreement['disagree'] / $ringAgreement['total']) * 100) : 0 }}%</h5>
                                <span class="text-muted fs-10">Disagreement</span>
                            </div>
                        </div>
                    @else
                        <p class="text-muted fs-11 mb-0">Not enough data with all three rings available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── RAGAS Evaluation Metrics ── --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="card-title mb-0 fs-13">
                    <iconify-icon icon="iconamoon:certificate-badge-duotone" class="text-success me-1"></iconify-icon>
                    RAGAS Evaluation Averages
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted fs-11 mb-3">System-wide averages from automated quality evaluation on every query</p>
                <div id="ragasRadarChart" style="height: 260px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- ── Per-Domain Safety Profiles ── --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="card-title mb-0 fs-13">
                    <iconify-icon icon="iconamoon:category-duotone" class="text-info me-1"></iconify-icon>
                    Domain Safety Profiles
                </h6>
            </div>
            <div class="card-body p-0">
                @if ($domainSafety->count())
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 fs-12">
                            <thead>
                                <tr>
                                    <th>Domain</th>
                                    <th class="text-center">Queries</th>
                                    <th class="text-center">Composite</th>
                                    <th class="text-center">Groundedness</th>
                                    <th class="text-center">NLI</th>
                                    <th class="text-center">Confidence</th>
                                    <th>Safety Distribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($domainSafety as $domain)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $domain['color'] }}-subtle text-{{ $domain['color'] }}">
                                                <iconify-icon icon="iconamoon:{{ $domain['icon'] ?? 'folder-duotone' }}" class="me-1"></iconify-icon>
                                                {{ $domain['name'] }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $domain['query_count'] }}</td>
                                        <td class="text-center fw-medium">{{ $domain['avg_composite'] ? number_format($domain['avg_composite'] * 100, 1) . '%' : 'N/A' }}</td>
                                        <td class="text-center">{{ $domain['avg_groundedness'] ? number_format($domain['avg_groundedness'] * 100, 0) . '%' : 'N/A' }}</td>
                                        <td class="text-center">{{ $domain['avg_lettuce'] ? number_format($domain['avg_lettuce'] * 100, 0) . '%' : 'N/A' }}</td>
                                        <td class="text-center">{{ $domain['avg_confidence'] ? number_format($domain['avg_confidence'] * 100, 0) . '%' : 'N/A' }}</td>
                                        <td>
                                            @php $total = $domain['green'] + $domain['yellow'] + $domain['red']; @endphp
                                            @if ($total > 0)
                                                <div class="d-flex align-items-center gap-1">
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar bg-success" style="width: {{ ($domain['green']/$total)*100 }}%"></div>
                                                        <div class="progress-bar bg-warning" style="width: {{ ($domain['yellow']/$total)*100 }}%"></div>
                                                        <div class="progress-bar bg-danger" style="width: {{ ($domain['red']/$total)*100 }}%"></div>
                                                    </div>
                                                    <span class="text-muted fs-10" style="min-width: 60px;">{{ $domain['green'] }}/{{ $domain['yellow'] }}/{{ $domain['red'] }}</span>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-3 text-center text-muted fs-12">No domain data available yet</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Model Router & Cost ── --}}
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="card-title mb-0 fs-13">
                    <iconify-icon icon="iconamoon:lightning-2-duotone" class="text-warning me-1"></iconify-icon>
                    Model Router & Cost
                </h6>
            </div>
            <div class="card-body">
                <div id="modelPieChart" style="height: 180px;"></div>
                <div class="border-top pt-2 mt-2">
                    <div class="d-flex justify-content-between fs-12 mb-1">
                        <span class="text-muted">GPT-4.1-mini (fast)</span>
                        <span class="fw-medium">{{ number_format($modelUsage['fast']) }} queries &middot; {{ number_format($modelTokens['fast']) }} tokens</span>
                    </div>
                    <div class="d-flex justify-content-between fs-12 mb-1">
                        <span class="text-muted">GPT-4.1 (complex)</span>
                        <span class="fw-medium">{{ number_format($modelUsage['complex']) }} queries &middot; {{ number_format($modelTokens['complex']) }} tokens</span>
                    </div>
                    <div class="d-flex justify-content-between fs-12 border-top pt-1 mt-1">
                        <span class="fw-semibold">Estimated Cost</span>
                        <span class="fw-bold text-success">${{ number_format($costTotal, 4) }}</span>
                    </div>
                    <p class="text-muted fs-10 mb-0 mt-1">
                        Intelligent routing saves <strong class="text-success">{{ $costSavings }}%</strong> vs using GPT-4.1 for all queries (${{ number_format($costIfAllComplex, 4) }})
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Agent Pipeline Performance ── --}}
<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="card-title mb-0 fs-13">
                    <iconify-icon icon="iconamoon:settings-duotone" class="text-warning me-1"></iconify-icon>
                    Agent Pipeline Performance
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 fs-12">
                        <thead>
                            <tr><th>Agent</th><th class="text-center">Runs</th><th class="text-center">Avg Latency</th><th class="text-center">Total Tokens</th></tr>
                        </thead>
                        <tbody>
                            @foreach (['content_safety' => 'Content Safety', 'retrieval' => 'Retrieval', 'generation' => 'Generation', 'verification' => 'Verification'] as $key => $label)
                                @php $stat = $agentStats[$key] ?? null; @endphp
                                <tr>
                                    <td class="fw-medium">{{ $label }}</td>
                                    <td class="text-center">{{ $stat ? number_format($stat->runs) : 0 }}</td>
                                    <td class="text-center">{{ $stat ? number_format($stat->avg_latency) . 'ms' : 'N/A' }}</td>
                                    <td class="text-center">{{ $stat ? number_format($stat->total_tokens) : 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Content Safety Gate ── --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="card-title mb-0 fs-13">
                    <iconify-icon icon="iconamoon:lock-duotone" class="text-danger me-1"></iconify-icon>
                    Content Safety Gate & Prompt Shields
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6 text-center">
                        <h3 class="fw-bold text-success mb-0">{{ $safetyAgentRuns }}</h3>
                        <span class="text-muted fs-11">Queries Screened</span>
                    </div>
                    <div class="col-6 text-center">
                        <h3 class="fw-bold text-danger mb-0">{{ $safetyBlocks }}</h3>
                        <span class="text-muted fs-11">Blocked by Safety</span>
                    </div>
                </div>
                <p class="text-muted fs-11 mb-2">Every query is screened through two safety layers before processing:</p>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center gap-2 p-2 rounded bg-light">
                        <iconify-icon icon="iconamoon:shield-yes-duotone" class="text-warning fs-16"></iconify-icon>
                        <div>
                            <span class="fw-medium fs-12">Azure Content Safety</span>
                            <span class="d-block text-muted fs-10">4 harm categories (Hate, Violence, Sexual, Self-Harm) with severity 0-6 scale</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 p-2 rounded bg-light">
                        <iconify-icon icon="iconamoon:lock-duotone" class="text-danger fs-16"></iconify-icon>
                        <div>
                            <span class="fw-medium fs-12">Prompt Shields</span>
                            <span class="d-block text-muted fs-10">Jailbreak detection + indirect prompt injection detection</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Governance Practices (Documentation Section) ── --}}
<div class="row g-3 mb-3">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header py-2">
                <h5 class="card-title mb-0 fs-13">
                    <iconify-icon icon="iconamoon:eye-duotone" class="text-primary me-1"></iconify-icon>
                    Transparency
                </h5>
            </div>
            <div class="card-body fs-13 text-muted">
                <ul class="mb-0 ps-3">
                    <li class="mb-2">Every answer includes inline <code>[Source N]</code> citations traceable to uploaded documents</li>
                    <li class="mb-2">VeriTrail DAG provides a complete provenance graph per query</li>
                    <li class="mb-2">Safety scores are shown to users with explanations of what each ring measures</li>
                    <li class="mb-0">OpenTelemetry trace IDs enable end-to-end distributed tracing</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header py-2">
                <h5 class="card-title mb-0 fs-13">
                    <iconify-icon icon="iconamoon:file-document-duotone" class="text-info me-1"></iconify-icon>
                    Data Governance
                </h5>
            </div>
            <div class="card-body fs-13 text-muted">
                <ul class="mb-0 ps-3">
                    <li class="mb-2"><strong>Data residency:</strong> All data stays within your Azure tenant</li>
                    <li class="mb-2"><strong>No training:</strong> Azure OpenAI does not use your data to train models</li>
                    <li class="mb-2"><strong>RBAC:</strong> Admin, Analyst, Viewer roles with middleware enforcement</li>
                    <li class="mb-0"><strong>Domain isolation:</strong> Separate prompts, citations, and indexes per domain</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header py-2">
                <h5 class="card-title mb-0 fs-13">
                    <iconify-icon icon="iconamoon:cloud-duotone" class="text-success me-1"></iconify-icon>
                    Azure Services ({{ 8 + ($costTotal > 0 ? 1 : 0) }})
                </h5>
            </div>
            <div class="card-body fs-13 text-muted">
                <div class="d-flex flex-wrap gap-1">
                    @foreach ([
                        'Azure OpenAI' => 'primary', 'AI Search' => 'info', 'Content Safety' => 'danger',
                        'Groundedness' => 'warning', 'Prompt Shields' => 'dark', 'Doc Intelligence' => 'success',
                        'Speech Service' => 'secondary', 'App Insights' => 'primary', 'Bing Search' => 'info',
                    ] as $service => $color)
                        <span class="badge bg-{{ $color }}-subtle text-{{ $color }} fs-10">{{ $service }}</span>
                    @endforeach
                </div>
                <p class="mt-2 mb-0 fs-11">
                    All services configured for minimal cost with free-tier usage where available.
                    Model router intelligently selects GPT-4.1-mini for simple queries, reserving GPT-4.1 for complex reasoning.
                </p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Safety Trend Line Chart ──
    var trendData = @json($safetyTrend);
    if (trendData.length > 0) {
        new ApexCharts(document.querySelector('#safetyTrendChart'), {
            chart: { type: 'line', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [
                { name: 'Composite', data: trendData.map(function (d) { return { x: d.date, y: (d.avg_composite * 100).toFixed(1) }; }) },
                { name: 'Groundedness', data: trendData.map(function (d) { return { x: d.date, y: d.avg_ground ? (d.avg_ground * 100).toFixed(1) : null }; }) },
                { name: 'NLI', data: trendData.map(function (d) { return { x: d.date, y: d.avg_lettuce ? (d.avg_lettuce * 100).toFixed(1) : null }; }) },
                { name: 'Confidence', data: trendData.map(function (d) { return { x: d.date, y: d.avg_confidence ? (d.avg_confidence * 100).toFixed(1) : null }; }) },
            ],
            colors: ['#6c5ce7', '#0d6efd', '#198754', '#0dcaf0'],
            stroke: { width: [3, 2, 2, 2], curve: 'smooth' },
            xaxis: { type: 'datetime', labels: { style: { fontSize: '10px' } } },
            yaxis: { min: 0, max: 100, labels: { formatter: function (v) { return v.toFixed(0) + '%'; }, style: { fontSize: '10px' } } },
            legend: { position: 'top', fontSize: '11px' },
            tooltip: { y: { formatter: function (v) { return v + '%'; } } },
            grid: { borderColor: 'rgba(0,0,0,0.05)' },
            annotations: {
                yaxis: [
                    { y: 75, borderColor: '#198754', strokeDashArray: 4, label: { text: 'Green threshold', style: { fontSize: '9px', color: '#198754', background: 'transparent' } } },
                    { y: 45, borderColor: '#dc3545', strokeDashArray: 4, label: { text: 'Red threshold', style: { fontSize: '9px', color: '#dc3545', background: 'transparent' } } },
                ]
            }
        }).render();
    } else {
        document.querySelector('#safetyTrendChart').innerHTML = '<div class="text-center text-muted py-5 fs-12">No trend data available yet</div>';
    }

    // ── Safety Distribution Pie ──
    var distData = @json($safetyDistribution);
    var distLabels = [], distValues = [], distColors = [];
    if (distData.green) { distLabels.push('Green (Safe)'); distValues.push(distData.green); distColors.push('#198754'); }
    if (distData.yellow) { distLabels.push('Yellow (Review)'); distValues.push(distData.yellow); distColors.push('#ffc107'); }
    if (distData.red) { distLabels.push('Red (Blocked)'); distValues.push(distData.red); distColors.push('#dc3545'); }
    if (distValues.length > 0) {
        new ApexCharts(document.querySelector('#safetyPieChart'), {
            chart: { type: 'donut', height: 250, fontFamily: 'inherit' },
            series: distValues,
            labels: distLabels,
            colors: distColors,
            plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', fontSize: '12px' } } } } },
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { style: { fontSize: '11px' } }
        }).render();
    } else {
        document.querySelector('#safetyPieChart').innerHTML = '<div class="text-muted fs-12">No data</div>';
    }

    // ── RAGAS Radar Chart ──
    var ragas = @json($ragasAvg);
    var ragasValues = [
        (ragas.faithfulness ? (ragas.faithfulness * 100).toFixed(0) : 0),
        (ragas.answer_relevancy ? (ragas.answer_relevancy * 100).toFixed(0) : 0),
        (ragas.context_precision ? (ragas.context_precision * 100).toFixed(0) : 0),
        (ragas.context_recall ? (ragas.context_recall * 100).toFixed(0) : 0),
    ];
    if (ragasValues.some(function (v) { return v > 0; })) {
        new ApexCharts(document.querySelector('#ragasRadarChart'), {
            chart: { type: 'radar', height: 260, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [{ name: 'RAGAS Score', data: ragasValues.map(Number) }],
            xaxis: { categories: ['Faithfulness', 'Relevancy', 'Precision', 'Recall'] },
            yaxis: { show: false, min: 0, max: 100 },
            colors: ['#198754'],
            fill: { opacity: 0.2 },
            markers: { size: 4 },
            stroke: { width: 2 },
            plotOptions: { radar: { polygons: { strokeColors: 'rgba(0,0,0,0.1)' } } },
            dataLabels: { enabled: true, style: { fontSize: '11px' }, formatter: function (v) { return v + '%'; } }
        }).render();
    } else {
        document.querySelector('#ragasRadarChart').innerHTML = '<div class="text-center text-muted py-5 fs-12">No RAGAS data available yet</div>';
    }

    // ── Model Router Pie ──
    var modelData = @json($modelUsage);
    var mLabels = [], mValues = [], mColors = [];
    if (modelData.fast) { mLabels.push('GPT-4.1-mini'); mValues.push(modelData.fast); mColors.push('#0dcaf0'); }
    if (modelData.complex) { mLabels.push('GPT-4.1'); mValues.push(modelData.complex); mColors.push('#ffc107'); }
    if (mValues.length > 0) {
        new ApexCharts(document.querySelector('#modelPieChart'), {
            chart: { type: 'donut', height: 180, fontFamily: 'inherit' },
            series: mValues,
            labels: mLabels,
            colors: mColors,
            plotOptions: { pie: { donut: { size: '60%' } } },
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { style: { fontSize: '11px' } }
        }).render();
    } else {
        document.querySelector('#modelPieChart').innerHTML = '<div class="text-muted fs-12 text-center py-3">No model data</div>';
    }
});
</script>
@endpush

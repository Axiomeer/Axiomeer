@extends('layouts.app')

@section('title', 'Performance Analytics')
@section('page-title', 'Analytics')

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">Performance Analytics</h4>
        <p class="text-muted mb-0 fs-13">System-wide RAG pipeline performance and safety metrics</p>
    </div>
</div>

{{-- KPI Row 1 --}}
<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-primary-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:comment-duotone" class="fs-24 text-primary"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Total Queries</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($totalQueries) }}</h3>
                        <p class="text-muted mb-0 fs-12">
                            <span class="text-success">{{ $completedQueries }}</span> completed,
                            <span class="text-danger">{{ $failedQueries }}</span> failed
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-info-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:speed-duotone" class="fs-24 text-info"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Avg Latency</p>
                        <h3 class="mb-0 fw-bold">{{ $avgLatency ? number_format($avgLatency) . 'ms' : '&mdash;' }}</h3>
                        <p class="text-muted mb-0 fs-12">End-to-end pipeline</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-warning-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:trend-up-duotone" class="fs-24 text-warning"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Avg Groundedness</p>
                        <h3 class="mb-0 fw-bold">{{ $avgGroundedness ? number_format($avgGroundedness * 100, 1) . '%' : '&mdash;' }}</h3>
                        <p class="text-muted mb-0 fs-12">Azure Groundedness API</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-danger-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:shield-yes-duotone" class="fs-24 text-danger"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Hallucinations Blocked</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($hallucinationsBlocked) }}</h3>
                        <p class="text-muted mb-0 fs-12">Three-ring defense</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- KPI Row 2 --}}
<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-success-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:target-duotone" class="fs-24 text-success"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Composite Safety</p>
                        <h3 class="mb-0 fw-bold">{{ $avgComposite ? number_format($avgComposite * 100, 1) . '%' : '&mdash;' }}</h3>
                        <p class="text-muted mb-0 fs-12">Weighted average</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-secondary-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:coin-duotone" class="fs-24 text-secondary"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Total Tokens</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($totalTokens) }}</h3>
                        <p class="text-muted mb-0 fs-12">Azure OpenAI usage</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Safety Distribution --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Safety Level Distribution</h5>
            </div>
            <div class="card-body">
                @if (!empty($safetyDistribution))
                    @php
                        $total = array_sum($safetyDistribution);
                        $colors = ['green' => 'success', 'yellow' => 'warning', 'red' => 'danger'];
                    @endphp
                    <div class="d-flex flex-column gap-3">
                        @foreach (['green', 'yellow', 'red'] as $level)
                            @php
                                $count = $safetyDistribution[$level] ?? 0;
                                $pct = $total > 0 ? ($count / $total) * 100 : 0;
                            @endphp
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-medium">
                                        <span class="badge bg-{{ $colors[$level] }} me-1">{{ ucfirst($level) }}</span>
                                        {{ $count }} queries
                                    </span>
                                    <span class="fw-medium">{{ number_format($pct, 1) }}%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $colors[$level] }}" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <iconify-icon icon="iconamoon:shield-yes-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                        <p class="text-muted fs-13 mb-0">No safety data yet. Process queries to see distribution.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Domain Breakdown --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Performance by Domain</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Queries</th>
                                <th>Avg Latency</th>
                                <th>Groundedness</th>
                                <th>Composite</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($domainStats as $ds)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $ds['color'] ?? 'secondary' }}-subtle text-{{ $ds['color'] ?? 'secondary' }}">
                                            {{ $ds['name'] }}
                                        </span>
                                    </td>
                                    <td>{{ $ds['query_count'] }}</td>
                                    <td>{{ $ds['avg_latency'] ? $ds['avg_latency'] . 'ms' : '—' }}</td>
                                    <td>{{ $ds['avg_groundedness'] ? number_format($ds['avg_groundedness'] * 100, 1) . '%' : '—' }}</td>
                                    <td>{{ $ds['avg_composite'] ? number_format($ds['avg_composite'] * 100, 1) . '%' : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No domain data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Daily Trend --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Query Volume (Last 7 Days)</h5>
            </div>
            <div class="card-body">
                @if (!empty($dailyTrend))
                    <div class="d-flex align-items-end gap-2" style="height: 120px;">
                        @php $maxCount = max($dailyTrend) ?: 1; @endphp
                        @foreach ($dailyTrend as $date => $count)
                            <div class="flex-fill text-center">
                                <div class="bg-primary rounded-top mx-auto" style="width: 32px; height: {{ max(4, ($count / $maxCount) * 100) }}px;"></div>
                                <div class="text-muted fs-11 mt-1">{{ \Carbon\Carbon::parse($date)->format('D') }}</div>
                                <div class="fw-medium fs-12">{{ $count }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <iconify-icon icon="iconamoon:trend-up-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                        <p class="text-muted fs-13 mb-0">No query data in the last 7 days.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

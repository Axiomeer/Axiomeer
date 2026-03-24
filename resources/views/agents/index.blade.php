@extends('layouts.app')

@section('title', 'Agent Pipeline')
@section('page-title', 'Agent Pipeline')

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">Agent Pipeline</h4>
        <p class="text-muted mb-0 fs-13">Multi-agent orchestration: Supervisor &rarr; Retrieval &rarr; Generation &rarr; Verification</p>
    </div>
</div>

{{-- Pipeline Visualization --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Pipeline Architecture</h5>
    </div>
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            @foreach ($agentTypes as $type => $agent)
                <div class="text-center flex-fill">
                    <div class="avatar-md rounded-circle bg-{{ $agent['color'] }}-subtle d-flex align-items-center justify-content-center mx-auto mb-2">
                        <iconify-icon icon="{{ $agent['icon'] }}" class="fs-28 text-{{ $agent['color'] }}"></iconify-icon>
                    </div>
                    <h6 class="fw-semibold mb-0">{{ $agent['label'] }}</h6>
                    <p class="text-muted fs-11 mb-0">{{ $agent['description'] }}</p>

                    {{-- Stats below each agent --}}
                    @php $stats = $agentStats[$type]; @endphp
                    <div class="mt-2">
                        <span class="fs-11 text-muted">{{ $stats['total'] }} runs</span>
                        @if ($stats['avg_latency'])
                            <span class="fs-11 text-muted">&middot; {{ $stats['avg_latency'] }}ms avg</span>
                        @endif
                    </div>
                </div>

                @if (!$loop->last)
                    <div class="text-muted flex-shrink-0">
                        <iconify-icon icon="iconamoon:arrow-right-2-duotone" class="fs-24"></iconify-icon>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>

{{-- Pipeline KPIs --}}
<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-primary-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:lightning-2-duotone" class="fs-24 text-primary"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Total Agent Runs</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($totalRuns) }}</h3>
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
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Avg Pipeline Latency</p>
                        <h3 class="mb-0 fw-bold">{{ $avgPipelineLatency ? number_format($avgPipelineLatency) . 'ms' : '&mdash;' }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach (['content_safety', 'retrieval', 'generation', 'verification'] as $type)
        @php $s = $agentStats[$type]; $a = $agentTypes[$type]; @endphp
    @endforeach

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-success-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:check-circle-1-duotone" class="fs-24 text-success"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Success Rate</p>
                        @php
                            $totalCompleted = collect($agentStats)->sum('completed');
                            $totalAll = collect($agentStats)->sum('total');
                            $successRate = $totalAll > 0 ? ($totalCompleted / $totalAll) * 100 : 0;
                        @endphp
                        <h3 class="mb-0 fw-bold">{{ $totalAll > 0 ? number_format($successRate, 1) . '%' : '&mdash;' }}</h3>
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
                        <h3 class="mb-0 fw-bold">{{ number_format(collect($agentStats)->sum('total_tokens')) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Per-Agent Breakdown --}}
<div class="row">
    @foreach ($agentTypes as $type => $agent)
        @php $stats = $agentStats[$type]; @endphp
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-header py-2">
                    <div class="d-flex align-items-center gap-2">
                        <iconify-icon icon="{{ $agent['icon'] }}" class="fs-18 text-{{ $agent['color'] }}"></iconify-icon>
                        <h6 class="card-title mb-0 fs-14">{{ $agent['label'] }}</h6>
                    </div>
                </div>
                <div class="card-body py-2">
                    <table class="table table-borderless table-sm mb-0 fs-13">
                        <tr>
                            <td class="text-muted">Total Runs</td>
                            <td class="fw-medium text-end">{{ $stats['total'] }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Completed</td>
                            <td class="fw-medium text-end text-success">{{ $stats['completed'] }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Failed</td>
                            <td class="fw-medium text-end text-danger">{{ $stats['failed'] }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Avg Latency</td>
                            <td class="fw-medium text-end">{{ $stats['avg_latency'] ? $stats['avg_latency'] . 'ms' : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tokens</td>
                            <td class="fw-medium text-end">{{ number_format($stats['total_tokens']) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Recent Agent Runs --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Recent Agent Runs</h5>
        <form method="GET" class="d-flex gap-2">
            <select class="form-select form-select-sm" name="agent_type" style="width: auto;" onchange="this.form.submit()">
                <option value="">All Agents</option>
                @foreach ($agentTypes as $type => $agent)
                    <option value="{{ $type }}" {{ request('agent_type') === $type ? 'selected' : '' }}>
                        {{ $agent['label'] }}
                    </option>
                @endforeach
            </select>
            <select class="form-select form-select-sm" name="status" style="width: auto;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>Running</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Trace</th>
                        <th>Agent</th>
                        <th>Query</th>
                        <th>Status</th>
                        <th>Latency</th>
                        <th>Tokens</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentRuns as $run)
                        <tr>
                            <td><code class="fs-11">{{ Str::limit($run->trace_id ?? '—', 16) }}</code></td>
                            <td>
                                @php $ag = $agentTypes[$run->agent_type] ?? ['label' => ucfirst($run->agent_type), 'color' => 'secondary']; @endphp
                                <span class="badge bg-{{ $ag['color'] }}-subtle text-{{ $ag['color'] }}">
                                    {{ $ag['label'] }}
                                </span>
                            </td>
                            <td class="fs-13">
                                @if ($run->relatedQuery)
                                    <a href="{{ route('query.show', $run->query_id) }}" class="text-decoration-none">
                                        {{ Str::limit($run->relatedQuery->question ?? 'Query #' . $run->query_id, 40) }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $rc = ['completed' => 'success', 'running' => 'info', 'failed' => 'danger', 'pending' => 'warning'];
                                @endphp
                                <span class="badge bg-{{ $rc[$run->status] ?? 'secondary' }}-subtle text-{{ $rc[$run->status] ?? 'secondary' }}">
                                    {{ ucfirst($run->status) }}
                                </span>
                            </td>
                            <td>{{ $run->latency_ms ? $run->latency_ms . 'ms' : '—' }}</td>
                            <td>{{ $run->token_count ? number_format($run->token_count) : '—' }}</td>
                            <td class="fs-12 text-muted text-nowrap">{{ $run->created_at->format('M d H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="text-center py-4">
                                    <iconify-icon icon="iconamoon:lightning-2-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                                    <h6 class="fw-semibold mb-1">No agent runs yet</h6>
                                    <p class="text-muted fs-13 mb-0">Agent runs will appear here as queries are processed through the pipeline.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($recentRuns->hasPages())
    <div class="d-flex justify-content-center">
        {{ $recentRuns->links() }}
    </div>
@endif

@endsection

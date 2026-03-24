@extends('layouts.app')

@section('title', 'Query Result')
@section('page-title', 'Ask Question')

@section('content')

<div class="row mb-3">
    <div class="col">
        <a href="{{ route('query.index') }}" class="text-muted fs-13 text-decoration-none">
            <i class="bx bx-arrow-back me-1"></i> Back to queries
        </a>
    </div>
</div>

<div class="row">
    {{-- Main Content --}}
    <div class="col-lg-8">
        {{-- Question Card --}}
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-3">
                    <div class="avatar-sm rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                        <iconify-icon icon="iconamoon:profile-duotone" class="fs-20 text-primary"></iconify-icon>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold">{{ $query->user->name ?? 'User' }}</span>
                            <span class="text-muted fs-12">{{ $query->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mb-0">{{ $query->question }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Answer Card --}}
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-3">
                    <div class="avatar-sm rounded-circle bg-info-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                        <iconify-icon icon="iconamoon:lightning-2-duotone" class="fs-20 text-info"></iconify-icon>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold">Axiomeer</span>
                            @if ($query->safety_level)
                                @php
                                    $safetyColors = ['green' => 'success', 'yellow' => 'warning', 'red' => 'danger'];
                                    $safetyLabels = ['green' => 'Grounded', 'yellow' => 'Review', 'red' => 'Blocked'];
                                @endphp
                                <span class="badge bg-{{ $safetyColors[$query->safety_level] ?? 'secondary' }}-subtle text-{{ $safetyColors[$query->safety_level] ?? 'secondary' }}">
                                    <iconify-icon icon="iconamoon:shield-yes-duotone" class="me-1"></iconify-icon>
                                    {{ $safetyLabels[$query->safety_level] ?? ucfirst($query->safety_level) }}
                                </span>
                            @endif
                        </div>

                        @if ($query->status === 'completed' && $query->answer)
                            <div class="mb-0">{!! nl2br(e($query->answer)) !!}</div>
                        @elseif ($query->status === 'pending')
                            <div class="d-flex align-items-center gap-2 py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span class="text-muted">Your question is being processed by the agent pipeline...</span>
                            </div>
                        @elseif ($query->status === 'processing')
                            <div class="d-flex align-items-center gap-2 py-3">
                                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                                <span class="text-muted">Agents are retrieving and verifying the answer...</span>
                            </div>
                        @elseif ($query->status === 'failed')
                            <div class="alert alert-danger mb-0">
                                <iconify-icon icon="iconamoon:sign-warning-duotone" class="me-1"></iconify-icon>
                                The query could not be processed. This may be due to a service error or content safety filter.
                            </div>
                        @else
                            <p class="text-muted mb-0">No answer available yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Citations --}}
        @if ($query->citations->count())
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:link-chain-duotone" class="text-primary me-1"></iconify-icon>
                        Citations ({{ $query->citations->count() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Source</th>
                                    <th>Snippet</th>
                                    <th>Relevance</th>
                                    <th>Verdict</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($query->citations as $citation)
                                    <tr>
                                        <td>
                                            <div class="fw-medium fs-13">{{ Str::limit($citation->document_title ?? $citation->document->title ?? 'Unknown', 30) }}</div>
                                            @if ($citation->page_number)
                                                <small class="text-muted">Page {{ $citation->page_number }}</small>
                                            @endif
                                        </td>
                                        <td class="fs-13">{{ Str::limit($citation->source_snippet, 80) }}</td>
                                        <td>
                                            <span class="fw-medium">{{ number_format($citation->relevance_score * 100, 0) }}%</span>
                                        </td>
                                        <td>
                                            @php
                                                $verdictColors = ['supported' => 'success', 'partial' => 'warning', 'unsupported' => 'danger'];
                                            @endphp
                                            <span class="badge bg-{{ $verdictColors[$citation->verdict] ?? 'secondary' }}-subtle text-{{ $verdictColors[$citation->verdict] ?? 'secondary' }}">
                                                {{ ucfirst($citation->verdict ?? 'pending') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Agent Runs --}}
        @if ($query->agentRuns->count())
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:lightning-2-duotone" class="text-warning me-1"></iconify-icon>
                        Agent Pipeline Trace
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Status</th>
                                    <th>Latency</th>
                                    <th>Tokens</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($query->agentRuns as $run)
                                    <tr>
                                        <td class="fw-medium">{{ ucfirst(str_replace('_', ' ', $run->agent_type)) }}</td>
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Right Sidebar: Scores --}}
    <div class="col-lg-4">
        {{-- Status --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Query Info</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted fw-medium">Domain</td>
                        <td>
                            <span class="badge bg-{{ $query->domain->color ?? 'secondary' }}-subtle text-{{ $query->domain->color ?? 'secondary' }}">
                                {{ $query->domain->display_name }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">Status</td>
                        <td>
                            @php
                                $sc = ['pending' => 'warning', 'processing' => 'info', 'completed' => 'success', 'failed' => 'danger'];
                            @endphp
                            <span class="badge bg-{{ $sc[$query->status] ?? 'secondary' }}-subtle text-{{ $sc[$query->status] ?? 'secondary' }}">
                                {{ ucfirst($query->status) }}
                            </span>
                        </td>
                    </tr>
                    @if ($query->latency_ms)
                    <tr>
                        <td class="text-muted fw-medium">Latency</td>
                        <td>{{ number_format($query->latency_ms) }}ms</td>
                    </tr>
                    @endif
                    @if ($query->token_count)
                    <tr>
                        <td class="text-muted fw-medium">Tokens</td>
                        <td>{{ number_format($query->token_count) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted fw-medium">Asked</td>
                        <td class="fs-13">{{ $query->created_at->format('M d, Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Safety Scores --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:shield-yes-duotone" class="text-danger me-1"></iconify-icon>
                    Safety Scores
                </h5>
            </div>
            <div class="card-body">
                @if ($query->composite_safety_score !== null)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium fs-13">Composite Safety</span>
                            <span class="fw-bold">{{ number_format($query->composite_safety_score * 100, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            @php $pct = $query->composite_safety_score * 100; @endphp
                            <div class="progress-bar bg-{{ $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>

                    @if ($query->groundedness_score !== null)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fs-13 text-muted">Azure Groundedness</span>
                            <span class="fw-medium">{{ number_format($query->groundedness_score * 100, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-primary" style="width: {{ $query->groundedness_score * 100 }}%"></div>
                        </div>
                    </div>
                    @endif

                    @if ($query->lettuce_score !== null)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fs-13 text-muted">LettuceDetect</span>
                            <span class="fw-medium">{{ number_format($query->lettuce_score * 100, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-success" style="width: {{ $query->lettuce_score * 100 }}%"></div>
                        </div>
                    </div>
                    @endif

                    @if ($query->confidence_score !== null)
                    <div class="mb-0">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fs-13 text-muted">Confidence (SRLM)</span>
                            <span class="fw-medium">{{ number_format($query->confidence_score * 100, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-info" style="width: {{ $query->confidence_score * 100 }}%"></div>
                        </div>
                    </div>
                    @endif
                @else
                    <div class="text-center py-3">
                        <iconify-icon icon="iconamoon:shield-yes-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                        <p class="text-muted fs-13 mb-0">Safety scores will appear once the query is processed.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

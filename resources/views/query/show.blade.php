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

{{-- Pipeline Progress Stepper --}}
@if ($query->agentRuns->count())
    @php
        $pipelineSteps = [
            'content_safety' => ['label' => 'Content Safety', 'icon' => 'iconamoon:shield-yes-duotone', 'color' => 'warning'],
            'retrieval' => ['label' => 'Retrieval', 'icon' => 'iconamoon:search-duotone', 'color' => 'info'],
            'generation' => ['label' => 'Generation', 'icon' => 'iconamoon:lightning-2-duotone', 'color' => 'primary'],
            'verification' => ['label' => 'Verification', 'icon' => 'iconamoon:check-circle-1-duotone', 'color' => 'success'],
        ];
        $runsByType = $query->agentRuns->keyBy('agent_type');
    @endphp
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex align-items-center justify-content-between position-relative">
                {{-- Connecting line --}}
                <div class="position-absolute" style="top: 50%; left: 40px; right: 40px; height: 2px; background: var(--bs-border-color); z-index: 0;"></div>

                @foreach ($pipelineSteps as $type => $step)
                    @php
                        $run = $runsByType[$type] ?? null;
                        $status = $run->status ?? 'pending';
                        $statusIcon = match($status) {
                            'completed' => 'bx-check',
                            'failed' => 'bx-x',
                            'running' => 'bx-loader-alt bx-spin',
                            default => 'bx-time-five',
                        };
                        $ringColor = match($status) {
                            'completed' => 'success',
                            'failed' => 'danger',
                            'running' => 'info',
                            default => 'secondary',
                        };
                    @endphp
                    <div class="text-center position-relative" style="z-index: 1; flex: 1;">
                        <div class="avatar-sm rounded-circle bg-{{ $ringColor }} d-flex align-items-center justify-content-center mx-auto mb-1 shadow-sm"
                             style="width: 42px; height: 42px; border: 3px solid var(--bs-{{ $ringColor }});">
                            <i class="bx {{ $statusIcon }} text-white fs-18"></i>
                        </div>
                        <div class="fw-semibold fs-12">{{ $step['label'] }}</div>
                        @if ($run && $run->latency_ms)
                            <div class="text-muted fs-11">{{ $run->latency_ms }}ms</div>
                        @elseif ($status === 'running')
                            <div class="text-info fs-11">Running...</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

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
                        @elseif ($query->status === 'failed')
                            <div class="alert alert-danger mb-0">
                                <iconify-icon icon="iconamoon:sign-warning-duotone" class="me-1"></iconify-icon>
                                {{ $query->answer ?: 'The query could not be processed. This may be due to a service error or content safety filter.' }}
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

        {{-- VeriTrail Provenance DAG --}}
        @if ($query->provenance_dag)
            @php $dag = $query->provenance_dag; @endphp
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:eye-duotone" class="text-info me-1"></iconify-icon>
                        VeriTrail Provenance DAG
                    </h5>
                    <code class="fs-10 text-muted">{{ $dag['trace_id'] ?? 'N/A' }}</code>
                </div>
                <div class="card-body">
                    {{-- Pipeline Flow (core nodes only) --}}
                    @php
                        $coreTypes = ['question', 'gate', 'agent', 'verification', 'answer'];
                        $coreNodes = collect($dag['nodes'] ?? [])->whereIn('type', $coreTypes);
                        $nodeIcons = [
                            'input' => 'comment-duotone',
                            'safety_gate' => 'shield-yes-duotone',
                            'retrieval' => 'search-duotone',
                            'generation' => 'lightning-2-duotone',
                            'ring1' => 'check-circle-1-duotone',
                            'ring2' => 'leaf-duotone',
                            'ring3' => 'trend-up-duotone',
                            'output' => 'send-duotone',
                        ];
                        $nodeColorMap = [
                            'question' => 'primary',
                            'gate' => 'warning',
                            'agent' => 'info',
                            'verification' => 'danger',
                            'answer' => 'success',
                        ];
                    @endphp
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-1 mb-3">
                        @foreach ($coreNodes as $node)
                            @php $nc = $nodeColorMap[$node['type']] ?? 'secondary'; @endphp
                            <div class="text-center" style="flex: 1; min-width: 60px;">
                                <div class="avatar-xs rounded-circle bg-{{ $nc }}-subtle d-flex align-items-center justify-content-center mx-auto mb-1">
                                    <iconify-icon icon="iconamoon:{{ $nodeIcons[$node['id']] ?? 'circle-duotone' }}" class="text-{{ $nc }}"></iconify-icon>
                                </div>
                                <div class="fw-medium fs-10">{{ $node['label'] }}</div>
                                @if (isset($node['chunks_retrieved']))
                                    <div class="text-muted fs-10">{{ $node['chunks_retrieved'] }} chunks</div>
                                @elseif (isset($node['tokens']))
                                    <div class="text-muted fs-10">{{ number_format($node['tokens']) }} tok</div>
                                @elseif (isset($node['score']))
                                    <div class="text-muted fs-10">{{ number_format(($node['score'] ?? 0) * 100, 0) }}%</div>
                                @elseif (isset($node['passed']))
                                    <div class="text-{{ $node['passed'] ? 'success' : 'danger' }} fs-10">{{ $node['passed'] ? 'Passed' : 'Blocked' }}</div>
                                @endif
                            </div>
                            @if (!$loop->last)
                                <iconify-icon icon="iconamoon:arrow-right-2-duotone" class="text-muted mt-2"></iconify-icon>
                            @endif
                        @endforeach
                    </div>

                    {{-- Claim-Level Backward Trace --}}
                    @php $claimNodes = collect($dag['nodes'] ?? [])->where('type', 'claim'); @endphp
                    @if ($claimNodes->count())
                        <hr class="my-2">
                        <h6 class="fw-semibold fs-12 mb-2">
                            <iconify-icon icon="iconamoon:link-chain-duotone" class="me-1"></iconify-icon>
                            Claim-Level Trace ({{ $claimNodes->count() }} claims)
                        </h6>
                        <div class="d-flex flex-column gap-1">
                            @foreach ($claimNodes->take(8) as $claim)
                                @php $isSupported = ($claim['verdict'] ?? '') === 'supported'; @endphp
                                <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: var(--bs-{{ $isSupported ? 'success' : 'danger' }}-bg-subtle, rgba({{ $isSupported ? '25,135,84' : '220,53,69' }},0.05));">
                                    <i class="bx bx-{{ $isSupported ? 'check' : 'x' }} text-{{ $isSupported ? 'success' : 'danger' }} fs-16"></i>
                                    <span class="fs-11 flex-grow-1">{{ $claim['label'] }}</span>
                                    @if (isset($claim['confidence']))
                                        <span class="badge bg-{{ $isSupported ? 'success' : 'danger' }}-subtle text-{{ $isSupported ? 'success' : 'danger' }} fs-10">
                                            {{ number_format(($claim['confidence'] ?? 0) * 100, 0) }}%
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                            @if ($claimNodes->count() > 8)
                                <div class="text-muted fs-11 text-center">+ {{ $claimNodes->count() - 8 }} more claims</div>
                            @endif
                        </div>
                    @endif

                    {{-- Error Localization --}}
                    @if (!empty($dag['metadata']['error_localization']))
                        <hr class="my-2">
                        <h6 class="fw-semibold fs-12 mb-2 text-danger">
                            <iconify-icon icon="iconamoon:sign-warning-duotone" class="me-1"></iconify-icon>
                            Error Localization
                        </h6>
                        @foreach (array_slice($dag['metadata']['error_localization'], 0, 3) as $err)
                            <div class="alert alert-danger py-1 px-2 mb-1 fs-11">
                                <strong>Ungrounded:</strong> {{ Str::limit($err['claim'] ?? '', 80) }}
                                <br><span class="text-muted">Stage: {{ $err['localized_to'] ?? 'generation' }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endif

        {{-- RAGAS Evaluation Metrics --}}
        @php $evalMetric = $query->evaluationMetrics->first(); @endphp
        @if ($evalMetric)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:trend-up-duotone" class="text-primary me-1"></iconify-icon>
                        RAGAS Evaluation
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @php
                            $ragasMetrics = [
                                ['label' => 'Faithfulness', 'value' => $evalMetric->faithfulness, 'color' => 'primary'],
                                ['label' => 'Answer Relevancy', 'value' => $evalMetric->answer_relevancy, 'color' => 'info'],
                                ['label' => 'Context Precision', 'value' => $evalMetric->context_precision, 'color' => 'warning'],
                                ['label' => 'Context Recall', 'value' => $evalMetric->context_recall, 'color' => 'success'],
                            ];
                        @endphp
                        @foreach ($ragasMetrics as $m)
                            <div class="col-6">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fs-12 text-muted">{{ $m['label'] }}</span>
                                    <span class="fw-medium fs-12">{{ $m['value'] !== null ? number_format($m['value'] * 100, 0) . '%' : 'N/A' }}</span>
                                </div>
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-{{ $m['color'] }}" style="width: {{ ($m['value'] ?? 0) * 100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($evalMetric->total_claims)
                        <div class="mt-2 pt-2 border-top">
                            <div class="d-flex justify-content-between fs-12">
                                <span class="text-muted">Claims: {{ $evalMetric->supported_claims }}/{{ $evalMetric->total_claims }} supported</span>
                                <span class="text-muted">Groundedness: {{ $evalMetric->groundedness_pct !== null ? number_format($evalMetric->groundedness_pct * 100, 0) . '%' : 'N/A' }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Right Sidebar --}}
    <div class="col-lg-4">
        {{-- Query Info --}}
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
                            @php $sc = ['pending' => 'warning', 'processing' => 'info', 'completed' => 'success', 'failed' => 'danger']; @endphp
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
                    @php
                        $genRun = $query->agentRuns->where('agent_type', 'generation')->first();
                        $modelUsed = $genRun && is_array($genRun->output) ? ($genRun->output['model_router'] ?? null) : null;
                    @endphp
                    @if ($modelUsed)
                    <tr>
                        <td class="text-muted fw-medium">Model</td>
                        <td>
                            <span class="badge bg-{{ $modelUsed === 'complex' ? 'warning' : 'info' }}-subtle text-{{ $modelUsed === 'complex' ? 'warning' : 'info' }}">
                                {{ $modelUsed === 'complex' ? 'GPT-4.1 (Complex)' : 'GPT-4.1-mini (Fast)' }}
                            </span>
                        </td>
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
                            <div class="progress-bar bg-{{ $pct >= 75 ? 'success' : ($pct >= 45 ? 'warning' : 'danger') }}"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>

                    @if ($query->groundedness_score !== null)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fs-13 text-muted">Ring 1: Azure Groundedness</span>
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
                            <span class="fs-13 text-muted">Ring 2: LettuceDetect</span>
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
                            <span class="fs-13 text-muted">Ring 3: SRLM Confidence</span>
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

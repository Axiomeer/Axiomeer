@extends('layouts.app')

@section('title', $conversation->title ?? 'Query Result')
@section('page-title', 'Ask Question')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/vis-network/standalone/umd/vis-network.min.css">
<style>
    .chat-container { max-height: calc(100vh - 220px); overflow-y: auto; scroll-behavior: smooth; padding-bottom: 1rem; }
    .chat-bubble { max-width: 92%; animation: fadeInUp 0.3s ease; }
    .chat-bubble-user { margin-left: auto; }
    .chat-bubble-bot { margin-right: auto; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .typing-indicator span { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--bs-primary); animation: bounce 1.4s infinite ease-in-out both; margin: 0 2px; }
    .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
    .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
    @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
    .safety-pill { font-size: 10px; padding: 2px 8px; }
    .details-toggle { cursor: pointer; transition: all 0.2s; }
    .details-toggle:hover { background: rgba(var(--bs-primary-rgb), 0.08); }
    .dag-mini .dag-node { width: 32px; height: 32px; }
    .chat-input-area { position: sticky; bottom: 0; background: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color); }
    .explanation-tooltip { max-width: 300px; }
    /* Safety Cockpit */
    .safety-cockpit-card { border-radius: 12px; overflow: hidden; border: 1px solid var(--bs-border-color); }
    .cockpit-banner-green { background: linear-gradient(135deg, rgba(var(--bs-success-rgb), 0.15), rgba(var(--bs-success-rgb), 0.05)); border-left: 4px solid var(--bs-success); }
    .cockpit-banner-yellow { background: linear-gradient(135deg, rgba(var(--bs-warning-rgb), 0.15), rgba(var(--bs-warning-rgb), 0.05)); border-left: 4px solid var(--bs-warning); }
    .cockpit-banner-red { background: linear-gradient(135deg, rgba(var(--bs-danger-rgb), 0.15), rgba(var(--bs-danger-rgb), 0.05)); border-left: 4px solid var(--bs-danger); }
    .score-pill { font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
    .cockpit-score-big { font-size: 2rem; font-weight: 800; line-height: 1; }
    .cockpit-section { padding: 10px 14px; }
    .cockpit-section + .cockpit-section { border-top: 1px solid var(--bs-border-color); }
    .cockpit-toggle { cursor: pointer; user-select: none; font-size: 11px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; display: flex; align-items: center; justify-content: space-between; }
    .cockpit-toggle:hover { opacity: 0.8; }
    .agent-step { display: flex; align-items: center; gap: 0; flex: 1; }
    .agent-step-node { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px; }
    .agent-step-line { flex: 1; height: 2px; background: var(--bs-border-color); min-width: 12px; }
    .agent-step-line.done { background: var(--bs-success); }
    .vis-tooltip-custom { position: fixed; z-index: 9999; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 8px; padding: 8px 12px; font-size: 12px; max-width: 240px; pointer-events: none; box-shadow: 0 4px 16px rgba(0,0,0,0.15); }
</style>
@endpush

@section('content')

<div class="row">
    {{-- Main Chat Area --}}
    <div class="col-lg-8">
        {{-- Chat Header --}}
        <div class="card mb-2">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('query.index') }}" class="btn btn-sm btn-light">
                            <i class="bx bx-arrow-back"></i>
                        </a>
                        <div>
                            <h6 class="fw-semibold mb-0 fs-14">{{ $conversation->title ?? 'Conversation' }}</h6>
                            <span class="badge bg-{{ $query->domain->color ?? 'primary' }}-subtle text-{{ $query->domain->color ?? 'primary' }} fs-10">
                                <iconify-icon icon="{{ $query->domain->icon ?? 'iconamoon:category-duotone' }}" class="me-1"></iconify-icon>
                                {{ $query->domain->display_name }}
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-1">
                        @if ($conversation)
                            <span class="text-muted fs-11">{{ $conversationQueries->count() }} messages</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Chat Messages --}}
        <div class="card">
            <div class="card-body chat-container p-3" id="chatContainer">
                @php $messages = $conversation ? $conversationQueries : collect([$query]); @endphp

                @foreach ($messages as $msg)
                    {{-- User Message --}}
                    <div class="chat-bubble chat-bubble-user mb-3">
                        <div class="d-flex gap-2 justify-content-end">
                            <div>
                                <div class="bg-primary text-white rounded-3 p-3 rounded-end-0">
                                    <p class="mb-0 fs-14">{{ $msg->question }}</p>
                                </div>
                                <div class="text-end mt-1 d-flex justify-content-end align-items-center gap-2">
                                    <button class="btn btn-sm btn-light tts-btn p-0 px-1" data-msg-id="user-{{ $msg->id }}" data-text="{{ e($msg->question) }}" title="Read aloud (Azure Speech)">
                                        <iconify-icon icon="iconamoon:volume-up-duotone" class="fs-12 text-muted"></iconify-icon>
                                    </button>
                                    <span class="text-muted fs-10">{{ $msg->created_at->format('M d, H:i') }}</span>
                                </div>
                            </div>
                            <div class="avatar-sm rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                                <iconify-icon icon="iconamoon:profile-duotone" class="fs-18 text-primary"></iconify-icon>
                            </div>
                        </div>
                    </div>

                    {{-- Bot Response --}}
                    <div class="chat-bubble chat-bubble-bot mb-3">
                        <div class="d-flex gap-2">
                            <div class="avatar-sm rounded-circle bg-info-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                                <iconify-icon icon="iconamoon:lightning-2-duotone" class="fs-18 text-info"></iconify-icon>
                            </div>
                            <div class="flex-grow-1">
                                @if ($msg->status === 'completed' && $msg->answer)
                                    {{-- Safety Badge --}}
                                    @if ($msg->safety_level)
                                        @php
                                            $safetyColors = ['green' => 'success', 'yellow' => 'warning', 'red' => 'danger'];
                                            $safetyLabels = ['green' => 'Grounded', 'yellow' => 'Review Needed', 'red' => 'Blocked'];
                                            $safetyExplanations = [
                                                'green' => 'This answer scored 75%+ on our composite safety check. All claims are well-supported by source documents.',
                                                'yellow' => 'This answer scored between 45-74%. Some claims may need manual verification against source documents.',
                                                'red' => 'This answer scored below 45%. High hallucination risk detected - claims are poorly supported by sources.',
                                            ];
                                        @endphp
                                        <div class="mb-2">
                                            <span class="badge bg-{{ $safetyColors[$msg->safety_level] ?? 'secondary' }}-subtle text-{{ $safetyColors[$msg->safety_level] ?? 'secondary' }} safety-pill"
                                                  data-bs-toggle="tooltip" data-bs-placement="top"
                                                  title="{{ $safetyExplanations[$msg->safety_level] ?? '' }}">
                                                <iconify-icon icon="iconamoon:shield-yes-duotone" class="me-1"></iconify-icon>
                                                {{ $safetyLabels[$msg->safety_level] ?? ucfirst($msg->safety_level) }}
                                                @if ($msg->composite_safety_score !== null)
                                                    &middot; {{ number_format($msg->composite_safety_score * 100, 0) }}%
                                                @endif
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Answer Text --}}
                                    <div class="bg-light rounded-3 p-3 rounded-start-0 mb-2" id="answer-bubble-{{ $msg->id }}">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="fs-14 flex-grow-1">{!! nl2br(e($msg->answer)) !!}</div>
                                            <button class="btn btn-sm btn-light tts-btn flex-shrink-0 mt-1"
                                                    data-msg-id="{{ $msg->id }}"
                                                    data-text="{{ e($msg->answer) }}"
                                                    title="Read aloud (Azure Speech)">
                                                <iconify-icon icon="iconamoon:volume-up-duotone" class="fs-16 text-primary"></iconify-icon>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Expandable Details Buttons --}}
                                    @php $evalMetric = $msg->evaluationMetrics->first(); @endphp
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        @if ($msg->composite_safety_score !== null)
                                            <button class="btn btn-sm btn-outline-secondary details-toggle rounded-pill fs-11"
                                                    data-bs-toggle="collapse" data-bs-target="#safety-{{ $msg->id }}">
                                                <i class="bx bx-shield me-1"></i>Safety Scores
                                            </button>
                                        @endif
                                        @if ($msg->citations->count())
                                            <button class="btn btn-sm btn-outline-secondary details-toggle rounded-pill fs-11"
                                                    data-bs-toggle="collapse" data-bs-target="#citations-{{ $msg->id }}">
                                                <i class="bx bx-link me-1"></i>{{ $msg->citations->count() }} Sources
                                            </button>
                                        @endif
                                        @if ($msg->provenance_dag)
                                            <button class="btn btn-sm btn-info details-toggle rounded-pill fs-11"
                                                    data-bs-toggle="collapse" data-bs-target="#veritrial-{{ $msg->id }}">
                                                <iconify-icon icon="iconamoon:share-2-duotone" class="me-1"></iconify-icon>VeriTrail DAG
                                            </button>
                                        @endif
                                        @if ($evalMetric)
                                            <button class="btn btn-sm btn-outline-secondary details-toggle rounded-pill fs-11"
                                                    data-bs-toggle="collapse" data-bs-target="#ragas-{{ $msg->id }}">
                                                <i class="bx bx-bar-chart me-1"></i>RAGAS
                                            </button>
                                        @endif
                                        @if ($msg->agentRuns->count())
                                            <button class="btn btn-sm btn-outline-secondary details-toggle rounded-pill fs-11"
                                                    data-bs-toggle="collapse" data-bs-target="#agents-{{ $msg->id }}">
                                                <i class="bx bx-cog me-1"></i>Pipeline
                                            </button>
                                        @endif
                                        <button class="btn btn-sm btn-outline-info details-toggle rounded-pill fs-11 web-verify-btn"
                                                data-query="{{ e(Str::limit($msg->question, 200)) }}"
                                                data-answer="{{ e(Str::limit($msg->answer ?? '', 300)) }}"
                                                data-target="websearch-{{ $msg->id }}">
                                            <i class="bx bx-globe me-1"></i>Web Verify
                                        </button>
                                    </div>

                                    {{-- Panels container — newest opened panel appears at top --}}
                                    <div class="details-panels-container" id="panels-{{ $msg->id }}">

                                    {{-- Web Search Results Panel --}}
                                    <div class="collapse mb-2" id="websearch-{{ $msg->id }}">
                                        <div class="border border-info rounded p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="fw-semibold fs-12 mb-0">
                                                    <i class="bx bx-globe text-info me-1"></i>
                                                    Web Cross-Reference
                                                </h6>
                                                <button class="btn btn-sm p-0 text-muted panel-hide-btn" data-bs-toggle="collapse" data-bs-target="#websearch-{{ $msg->id }}">
                                                    <i class="bx bx-x fs-16"></i>
                                                </button>
                                            </div>
                                            <p class="text-muted fs-10 mb-2">Cross-checks claims against web sources via Bing Search API</p>
                                            <div class="web-search-results" id="websearch-results-{{ $msg->id }}">
                                                <div class="text-center py-2">
                                                    <i class="bx bx-loader-alt bx-spin text-info"></i>
                                                    <span class="text-muted fs-11 ms-1">Searching the web...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Safety Scores Collapse --}}
                                    @if ($msg->composite_safety_score !== null)
                                        <div class="collapse mb-2" id="safety-{{ $msg->id }}">
                                            <div class="border rounded p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="fw-semibold fs-12 mb-0">
                                                        <i class="bx bx-shield text-danger me-1"></i>
                                                        Three-Ring Hallucination Defense
                                                    </h6>
                                                    <button class="btn btn-sm p-0 text-muted panel-hide-btn" data-bs-toggle="collapse" data-bs-target="#safety-{{ $msg->id }}">
                                                        <i class="bx bx-x fs-16"></i>
                                                    </button>
                                                </div>
                                                <p class="text-muted fs-11 mb-2">Each answer is verified through three independent checks. The composite score determines the safety level.</p>
                                                @php
                                                    $rings = [
                                                        ['label' => 'Ring 1: Azure Groundedness', 'value' => $msg->groundedness_score, 'color' => 'primary', 'weight' => '50%',
                                                         'desc' => 'Compares the answer against source documents to detect unsupported statements.'],
                                                        ['label' => 'Ring 2: LettuceDetect NLI', 'value' => $msg->lettuce_score, 'color' => 'success', 'weight' => '30%',
                                                         'desc' => 'Decomposes the answer into individual claims and checks if each is supported by retrieved context.'],
                                                        ['label' => 'Ring 3: SRLM Confidence', 'value' => $msg->confidence_score, 'color' => 'info', 'weight' => '20%',
                                                         'desc' => 'The model evaluates its own confidence in the answer, identifying gaps in source coverage.'],
                                                    ];
                                                @endphp
                                                @foreach ($rings as $ring)
                                                    <div class="mb-2">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <div>
                                                                <span class="fw-medium fs-12">{{ $ring['label'] }}</span>
                                                                <span class="badge bg-{{ $ring['color'] }}-subtle text-{{ $ring['color'] }} ms-1 fs-10">{{ $ring['weight'] }}</span>
                                                            </div>
                                                            <span class="fw-bold fs-12">
                                                                @if ($ring['value'] !== null)
                                                                    {{ number_format($ring['value'] * 100, 1) }}%
                                                                @else
                                                                    <span class="text-muted" data-bs-toggle="tooltip" title="This ring's API call failed. Weight was redistributed to the other rings.">Unavailable</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <div class="progress mb-1" style="height: 4px;">
                                                            @if ($ring['value'] !== null)
                                                                <div class="progress-bar bg-{{ $ring['color'] }}" style="width: {{ $ring['value'] * 100 }}%"></div>
                                                            @else
                                                                <div class="progress-bar bg-secondary progress-bar-striped" style="width: 100%; opacity: 0.2;"></div>
                                                            @endif
                                                        </div>
                                                        <p class="text-muted fs-10 mb-0">
                                                            {{ $ring['desc'] }}
                                                            @if ($ring['value'] === null)
                                                                <span class="text-warning">(API unavailable — weight redistributed to active rings)</span>
                                                            @endif
                                                        </p>
                                                    </div>
                                                @endforeach
                                                <div class="border-top pt-2 mt-2">
                                                    @php $pct = $msg->composite_safety_score * 100; @endphp
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="fw-bold fs-12">Composite Safety</span>
                                                        <span class="fw-bold fs-12">{{ number_format($pct, 1) }}%</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-{{ $pct >= 75 ? 'success' : ($pct >= 45 ? 'warning' : 'danger') }}"
                                                             style="width: {{ $pct }}%"></div>
                                                    </div>
                                                    <p class="text-muted fs-10 mt-1 mb-0">
                                                        Formula: (Groundedness x 50%) + (LettuceDetect x 30%) + (Confidence x 20%)
                                                        @if ($msg->groundedness_score === null || $msg->lettuce_score === null || $msg->confidence_score === null)
                                                            <br><span class="text-warning">* Weights redistributed due to unavailable ring(s)</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Citations Collapse --}}
                                    @if ($msg->citations->count())
                                        <div class="collapse mb-2" id="citations-{{ $msg->id }}">
                                            <div class="border rounded p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="fw-semibold fs-12 mb-0">
                                                        <i class="bx bx-link text-primary me-1"></i>
                                                        Source Citations
                                                    </h6>
                                                    <button class="btn btn-sm p-0 text-muted panel-hide-btn" data-bs-toggle="collapse" data-bs-target="#citations-{{ $msg->id }}">
                                                        <i class="bx bx-x fs-16"></i>
                                                    </button>
                                                </div>
                                                @foreach ($msg->citations as $citation)
                                                    <div class="border-bottom py-2 {{ $loop->last ? 'border-bottom-0 pb-0' : '' }}">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="flex-grow-1">
                                                                @if ($citation->document_id)
                                                                    <a href="{{ route('documents.show', $citation->document_id) }}" class="fw-medium fs-12 text-decoration-none" target="_blank">
                                                                        <iconify-icon icon="iconamoon:file-document-duotone" class="me-1 fs-12"></iconify-icon>
                                                                        {{ Str::limit($citation->document_title ?? 'Source', 40) }}
                                                                        <iconify-icon icon="iconamoon:arrow-top-right-1-duotone" class="fs-10 ms-1"></iconify-icon>
                                                                    </a>
                                                                @else
                                                                    <span class="fw-medium fs-12">{{ Str::limit($citation->document_title ?? 'Source', 40) }}</span>
                                                                @endif
                                                                @if ($citation->page_number)
                                                                    <span class="text-muted fs-10 ms-1">p.{{ $citation->page_number }}</span>
                                                                @endif
                                                            </div>
                                                            <div class="d-flex gap-1">
                                                                <span class="badge bg-secondary-subtle text-secondary fs-10">{{ number_format(min(100, ($citation->relevance_score ?? 0) * 100), 0) }}%</span>
                                                                @php $verdictColors = ['supported' => 'success', 'partial' => 'warning', 'unsupported' => 'danger']; @endphp
                                                                <span class="badge bg-{{ $verdictColors[$citation->verdict] ?? 'secondary' }}-subtle text-{{ $verdictColors[$citation->verdict] ?? 'secondary' }} fs-10">
                                                                    {{ ucfirst($citation->verdict ?? 'pending') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <p class="text-muted fs-11 mb-0 mt-1">{{ Str::limit($citation->source_snippet, 120) }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- VeriTrail DAG (vis.js interactive graph) — collapsed by default --}}
                                    @if ($msg->provenance_dag)
                                        @php $dag = $msg->provenance_dag; @endphp
                                        <div class="collapse mb-2" id="veritrial-{{ $msg->id }}">
                                            <div class="border border-info rounded p-3" style="background: rgba(14,165,233,0.03);">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="fw-bold fs-13 mb-0 text-info">
                                                        <iconify-icon icon="iconamoon:share-2-duotone" class="me-1"></iconify-icon>
                                                        VeriTrail Provenance DAG
                                                    </h6>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <code class="fs-9 text-muted">{{ $dag['trace_id'] ?? '' }}</code>
                                                        <button class="btn btn-sm p-0 text-muted" data-bs-toggle="collapse" data-bs-target="#veritrial-{{ $msg->id }}">
                                                            <i class="bx bx-x fs-16"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="alert alert-info py-2 px-3 mb-2 fs-11" style="border-left: 3px solid var(--bs-info);">
                                                    <strong>What is this?</strong> This graph traces every step of the AI's reasoning. Your question flows through safety screening → document retrieval → answer generation → claim verification. Each claim in the answer is linked back to the source document that supports it.
                                                    <span class="text-success fw-medium">Green = grounded in sources.</span>
                                                    <span class="text-danger fw-medium">Red = unsupported claim.</span>
                                                    Click any node for details.
                                                </div>

                                                {{-- vis.js graph container --}}
                                                <div id="veritrial-graph-{{ $msg->id }}" style="height: 420px; border-radius: 8px; overflow: hidden; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color);"
                                                     data-dag="{{ json_encode($msg->provenance_dag) }}">
                                                </div>
                                                <div class="text-muted fs-10 mt-2 text-center d-flex align-items-center justify-content-center gap-3 flex-wrap">
                                                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#6366f1;"></span> Question</span>
                                                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#6b7280;"></span> Agent</span>
                                                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#0ea5e9;"></span> Verification</span>
                                                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#10b981;"></span> Supported</span>
                                                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ef4444;"></span> Unsupported</span>
                                                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f59e0b;"></span> Source</span>
                                                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#8b5cf6;"></span> Answer</span>
                                                </div>

                                                {{-- Error Localization (kept as text fallback) --}}
                                                @if (!empty($dag['metadata']['error_localization']))
                                                    <div class="border-top pt-2 mt-2">
                                                        <span class="fw-semibold fs-10 text-danger">Error Localization</span>
                                                        @foreach (array_slice($dag['metadata']['error_localization'], 0, 2) as $err)
                                                            <div class="text-danger fs-10 mt-1">
                                                                <iconify-icon icon="iconamoon:sign-warning-duotone" class="me-1"></iconify-icon>{{ Str::limit($err['claim'] ?? '', 80) }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    {{-- RAGAS Collapse --}}
                                    @if ($evalMetric)
                                        <div class="collapse mb-2" id="ragas-{{ $msg->id }}">
                                            <div class="border rounded p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="fw-semibold fs-12 mb-0">
                                                        <i class="bx bx-bar-chart text-primary me-1"></i>
                                                        RAGAS Evaluation
                                                    </h6>
                                                    <button class="btn btn-sm p-0 text-muted panel-hide-btn" data-bs-toggle="collapse" data-bs-target="#ragas-{{ $msg->id }}">
                                                        <i class="bx bx-x fs-16"></i>
                                                    </button>
                                                </div>
                                                <p class="text-muted fs-10 mb-2">Retrieval Augmented Generation Assessment - measures how well the system retrieves and generates answers.</p>
                                                <div class="row g-2">
                                                    @php
                                                        $ragasItems = [
                                                            ['label' => 'Faithfulness', 'value' => $evalMetric->faithfulness, 'color' => 'primary',
                                                             'help' => 'Are the claims in the answer supported by the retrieved context?'],
                                                            ['label' => 'Relevancy', 'value' => $evalMetric->answer_relevancy, 'color' => 'info',
                                                             'help' => 'Is the answer actually relevant to the question asked?'],
                                                            ['label' => 'Precision', 'value' => $evalMetric->context_precision, 'color' => 'warning',
                                                             'help' => 'Were the retrieved document chunks actually relevant to the question?'],
                                                            ['label' => 'Recall', 'value' => $evalMetric->context_recall, 'color' => 'success',
                                                             'help' => 'Were all the relevant documents in the knowledge base retrieved?'],
                                                        ];
                                                    @endphp
                                                    @foreach ($ragasItems as $r)
                                                        <div class="col-6">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="fs-11 text-muted" data-bs-toggle="tooltip" title="{{ $r['help'] }}">{{ $r['label'] }} <i class="bx bx-info-circle fs-10"></i></span>
                                                                <span class="fw-medium fs-11">{{ $r['value'] !== null ? number_format($r['value'] * 100, 0) . '%' : 'N/A' }}</span>
                                                            </div>
                                                            <div class="progress" style="height: 3px;">
                                                                <div class="progress-bar bg-{{ $r['color'] }}" style="width: {{ ($r['value'] ?? 0) * 100 }}%"></div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Agent Pipeline Collapse --}}
                                    @if ($msg->agentRuns->count())
                                        <div class="collapse mb-2" id="agents-{{ $msg->id }}">
                                            <div class="border rounded p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="fw-semibold fs-12 mb-0">
                                                        <i class="bx bx-cog text-warning me-1"></i>
                                                        Agent Pipeline
                                                    </h6>
                                                    <button class="btn btn-sm p-0 text-muted panel-hide-btn" data-bs-toggle="collapse" data-bs-target="#agents-{{ $msg->id }}">
                                                        <i class="bx bx-x fs-16"></i>
                                                    </button>
                                                </div>
                                                @foreach ($msg->agentRuns->sortBy('created_at') as $run)
                                                    <div class="d-flex align-items-center justify-content-between py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                        <div class="d-flex align-items-center gap-2">
                                                            @php
                                                                $statusIcons = ['completed' => 'bx-check text-success', 'failed' => 'bx-x text-danger', 'running' => 'bx-loader-alt bx-spin text-info'];
                                                            @endphp
                                                            <i class="bx {{ $statusIcons[$run->status] ?? 'bx-time-five text-muted' }} fs-14"></i>
                                                            <span class="fw-medium fs-12">{{ ucfirst(str_replace('_', ' ', $run->agent_type)) }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            @if ($run->latency_ms)
                                                                <span class="text-muted fs-10">{{ number_format($run->latency_ms) }}ms</span>
                                                            @endif
                                                            @if ($run->trace_id)
                                                                <code class="fs-9 text-muted" data-bs-toggle="tooltip" title="Trace: {{ $run->trace_id }} | Span: {{ $run->span_id }}">
                                                                    {{ Str::limit($run->span_id ?? '', 12) }}
                                                                </code>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    </div>{{-- end details-panels-container --}}

                                    {{-- Safety Cockpit --}}
                                    @if ($msg->status === 'completed' && $msg->safety_level && $msg->composite_safety_score !== null)
                                    @php
                                        $ckPct = round($msg->composite_safety_score * 100);
                                        $ckLevel = $msg->safety_level; // green / yellow / red
                                        $ckBannerClass = ['green' => 'cockpit-banner-green', 'yellow' => 'cockpit-banner-yellow', 'red' => 'cockpit-banner-red'][$ckLevel] ?? 'cockpit-banner-green';
                                        $ckColor = ['green' => 'success', 'yellow' => 'warning', 'red' => 'danger'][$ckLevel] ?? 'success';
                                        $ckLabelText = ['green' => 'GROUNDED', 'yellow' => 'REVIEW NEEDED', 'red' => 'BLOCKED'][$ckLevel] ?? 'GROUNDED';
                                        $ckIcon = ['green' => 'iconamoon:shield-yes-duotone', 'yellow' => 'iconamoon:shield-duotone', 'red' => 'iconamoon:shield-cross-duotone'][$ckLevel] ?? 'iconamoon:shield-yes-duotone';
                                        $dag = is_array($msg->provenance_dag) ? $msg->provenance_dag : (json_decode($msg->provenance_dag, true) ?? []);
                                        $claimNodesForCockpit = collect($dag['nodes'] ?? [])->where('type', 'claim');
                                    @endphp
                                    <div class="safety-cockpit-card mb-3">
                                        {{-- Banner --}}
                                        <div class="{{ $ckBannerClass }} cockpit-section py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                {{-- Left: shield icon + level --}}
                                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                    <iconify-icon icon="{{ $ckIcon }}" class="fs-28 text-{{ $ckColor }}"></iconify-icon>
                                                    <div>
                                                        <div class="fw-bold fs-13 text-{{ $ckColor }}">{{ $ckLabelText }}</div>
                                                        <div class="text-muted fs-10">Safety Cockpit</div>
                                                    </div>
                                                </div>
                                                {{-- Center: score pills --}}
                                                <div class="d-flex flex-wrap gap-1 flex-grow-1 justify-content-center">
                                                    @if ($msg->groundedness_score !== null)
                                                        <span class="score-pill bg-primary-subtle text-primary">
                                                            Groundedness: {{ number_format($msg->groundedness_score * 100, 0) }}%
                                                        </span>
                                                    @endif
                                                    @if ($msg->lettuce_score !== null)
                                                        <span class="score-pill bg-success-subtle text-success">
                                                            NLI: {{ number_format($msg->lettuce_score * 100, 0) }}%
                                                        </span>
                                                    @endif
                                                    @if ($msg->confidence_score !== null)
                                                        <span class="score-pill bg-info-subtle text-info">
                                                            Confidence: {{ number_format($msg->confidence_score * 100, 0) }}%
                                                        </span>
                                                    @endif
                                                </div>
                                                {{-- Right: composite score --}}
                                                <div class="text-end flex-shrink-0">
                                                    <div class="cockpit-score-big text-{{ $ckColor }}">{{ $ckPct }}%</div>
                                                    <div class="text-muted fs-10">Composite</div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Ring Breakdown (collapsible) --}}
                                        <div class="cockpit-section">
                                            <div class="cockpit-toggle text-muted mb-2" data-bs-toggle="collapse" data-bs-target="#cockpit-rings-{{ $msg->id }}">
                                                <span><iconify-icon icon="iconamoon:circle-duotone" class="me-1"></iconify-icon>Ring Breakdown</span>
                                                <iconify-icon icon="iconamoon:arrow-down-2-duotone"></iconify-icon>
                                            </div>
                                            <div class="collapse" id="cockpit-rings-{{ $msg->id }}">
                                                @php
                                                    $ckRings = [
                                                        ['label' => 'Ring 1: Azure Groundedness', 'value' => $msg->groundedness_score, 'color' => 'primary', 'weight' => '50%'],
                                                        ['label' => 'Ring 2: LettuceDetect NLI',  'value' => $msg->lettuce_score,     'color' => 'success', 'weight' => '30%'],
                                                        ['label' => 'Ring 3: SRLM Confidence',    'value' => $msg->confidence_score,  'color' => 'info',    'weight' => '20%'],
                                                    ];
                                                @endphp
                                                @foreach ($ckRings as $ckRing)
                                                    <div class="mb-2">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="fs-11 fw-medium">
                                                                {{ $ckRing['label'] }}
                                                                <span class="badge bg-{{ $ckRing['color'] }}-subtle text-{{ $ckRing['color'] }} ms-1 fs-9">{{ $ckRing['weight'] }}</span>
                                                            </span>
                                                            <span class="fw-bold fs-12 text-{{ $ckRing['color'] }}">
                                                                @if ($ckRing['value'] !== null)
                                                                    {{ number_format($ckRing['value'] * 100, 1) }}%
                                                                @else
                                                                    <span class="text-muted">N/A</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <div class="progress" style="height: 5px; border-radius: 4px;">
                                                            @if ($ckRing['value'] !== null)
                                                                <div class="progress-bar bg-{{ $ckRing['color'] }}" role="progressbar" style="width: {{ $ckRing['value'] * 100 }}%"></div>
                                                            @else
                                                                <div class="progress-bar bg-secondary progress-bar-striped" role="progressbar" style="width: 100%; opacity: 0.2;"></div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Claims Panel (collapsible) --}}
                                        @if ($claimNodesForCockpit->count())
                                        <div class="cockpit-section">
                                            <div class="cockpit-toggle text-muted mb-2" data-bs-toggle="collapse" data-bs-target="#cockpit-claims-{{ $msg->id }}">
                                                <span>
                                                    <iconify-icon icon="iconamoon:file-document-duotone" class="me-1"></iconify-icon>
                                                    Claims ({{ $claimNodesForCockpit->count() }})
                                                    &nbsp;
                                                    <span class="badge bg-success-subtle text-success fs-9">{{ $claimNodesForCockpit->where('verdict', 'supported')->count() }} supported</span>
                                                    <span class="badge bg-danger-subtle text-danger fs-9 ms-1">{{ $claimNodesForCockpit->where('verdict', '!=', 'supported')->count() }} unsupported</span>
                                                </span>
                                                <iconify-icon icon="iconamoon:arrow-down-2-duotone"></iconify-icon>
                                            </div>
                                            <div class="collapse" id="cockpit-claims-{{ $msg->id }}">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-borderless mb-0 fs-11">
                                                        <thead>
                                                            <tr class="text-muted">
                                                                <th class="fw-medium">Claim</th>
                                                                <th class="fw-medium text-center" style="width: 100px;">Verdict</th>
                                                                <th class="fw-medium text-end" style="width: 110px;">Source</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($claimNodesForCockpit as $ckClaim)
                                                                @php
                                                                    $ckVerdict = $ckClaim['verdict'] ?? 'unsupported';
                                                                    $ckVerdictColor = $ckVerdict === 'supported' ? 'success' : 'danger';
                                                                    $ckVerdictLabel = ucfirst($ckVerdict);
                                                                    $ckSource = $ckClaim['source'] ?? ($ckClaim['source_title'] ?? '');
                                                                @endphp
                                                                <tr>
                                                                    <td class="text-truncate" style="max-width: 200px;">
                                                                        {{ Str::limit($ckClaim['label'] ?? ($ckClaim['text'] ?? 'Claim'), 70) }}
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-{{ $ckVerdictColor }}-subtle text-{{ $ckVerdictColor }} fs-9">
                                                                            <iconify-icon icon="iconamoon:{{ $ckVerdict === 'supported' ? 'check-circle-1-duotone' : 'sign-times-circle-duotone' }}" class="me-1"></iconify-icon>
                                                                            {{ $ckVerdictLabel }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-end text-muted text-truncate" style="max-width: 110px;">
                                                                        {{ Str::limit($ckSource, 25) ?: '—' }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        {{-- Agent Timeline (collapsible) --}}
                                        @if ($msg->agentRuns->count())
                                        <div class="cockpit-section">
                                            <div class="cockpit-toggle text-muted mb-2" data-bs-toggle="collapse" data-bs-target="#cockpit-timeline-{{ $msg->id }}">
                                                <span><iconify-icon icon="iconamoon:history-duotone" class="me-1"></iconify-icon>Agent Timeline</span>
                                                <iconify-icon icon="iconamoon:arrow-down-2-duotone"></iconify-icon>
                                            </div>
                                            <div class="collapse" id="cockpit-timeline-{{ $msg->id }}">
                                                @php
                                                    $ckAgentIcons = [
                                                        'content_safety' => 'iconamoon:shield-yes-duotone',
                                                        'safety'         => 'iconamoon:shield-yes-duotone',
                                                        'retrieval'      => 'iconamoon:search-duotone',
                                                        'generation'     => 'iconamoon:lightning-2-duotone',
                                                        'verification'   => 'iconamoon:check-circle-1-duotone',
                                                    ];
                                                    $ckAgentColors = [
                                                        'content_safety' => '#ef4444',
                                                        'safety'         => '#ef4444',
                                                        'retrieval'      => '#3b82f6',
                                                        'generation'     => '#8b5cf6',
                                                        'verification'   => '#10b981',
                                                    ];
                                                    $ckSortedRuns = $msg->agentRuns->sortBy('created_at');
                                                @endphp
                                                <div class="d-flex align-items-center overflow-auto pb-2">
                                                    @foreach ($ckSortedRuns as $ckRun)
                                                        @php
                                                            $ckRunIcon = $ckAgentIcons[$ckRun->agent_type] ?? 'iconamoon:settings-duotone';
                                                            $ckRunColor = $ckAgentColors[$ckRun->agent_type] ?? '#6b7280';
                                                            $ckRunStatus = $ckRun->status;
                                                            $ckRunBg = $ckRunStatus === 'completed' ? '#10b981' : ($ckRunStatus === 'failed' ? '#ef4444' : '#6b7280');
                                                        @endphp
                                                        <div class="text-center flex-shrink-0" style="min-width: 70px;">
                                                            <div class="agent-step-node mx-auto mb-1" style="background: {{ $ckRunBg }}20; border: 2px solid {{ $ckRunBg }};">
                                                                <iconify-icon icon="{{ $ckRunIcon }}" style="color: {{ $ckRunBg }}; font-size: 16px;"></iconify-icon>
                                                            </div>
                                                            <div class="fw-medium fs-9" style="line-height:1.2;">{{ Str::limit(ucwords(str_replace('_',' ',$ckRun->agent_type)), 14) }}</div>
                                                            @if ($ckRun->latency_ms)
                                                                <div class="text-muted fs-9">{{ number_format($ckRun->latency_ms) }}ms</div>
                                                            @endif
                                                        </div>
                                                        @if (!$loop->last)
                                                            <div class="agent-step-line {{ $ckRunStatus === 'completed' ? 'done' : '' }} mx-1" style="min-width: 16px;"></div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    @endif
                                    {{-- /Safety Cockpit --}}

                                    <div class="mt-1">
                                        <span class="text-muted fs-10">
                                            {{ $msg->created_at->format('M d, H:i') }}
                                            @if ($msg->latency_ms)
                                                &middot; {{ number_format($msg->latency_ms) }}ms
                                            @endif
                                            @if ($msg->token_count)
                                                &middot; {{ number_format($msg->token_count) }} tokens
                                            @endif
                                        </span>
                                    </div>

                                @elseif ($msg->status === 'failed')
                                    <div class="alert alert-danger mb-0 fs-13">
                                        <iconify-icon icon="iconamoon:sign-warning-duotone" class="me-1"></iconify-icon>
                                        {{ $msg->answer ?: 'The query could not be processed. This may be due to a service error or content safety filter.' }}
                                    </div>
                                @elseif ($msg->status === 'processing')
                                    <div class="bg-light rounded-3 p-3 rounded-start-0">
                                        <div class="typing-indicator">
                                            <span></span><span></span><span></span>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted mb-0 fs-13">Processing...</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Chat Input --}}
            @if ($conversation)
                <div class="chat-input-area p-3">
                    <form method="POST" action="{{ route('query.store') }}" id="chatForm">
                        @csrf
                        <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                        <input type="hidden" name="domain_id" value="{{ $query->domain_id }}">
                        <div class="input-group">
                            <button type="button" class="btn btn-light" id="chat-mic-btn" title="Voice input">
                                <iconify-icon icon="iconamoon:microphone-duotone"></iconify-icon>
                            </button>
                            <input type="text" class="form-control" name="question" id="chatInput"
                                   placeholder="Ask a follow-up question..." autocomplete="off" required>
                            <button type="submit" class="btn btn-primary">
                                <iconify-icon icon="iconamoon:send-duotone"></iconify-icon>
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Right Sidebar --}}
    <div class="col-lg-4">
        {{-- Query Info --}}
        <div class="card">
            <div class="card-header py-2">
                <h6 class="card-title mb-0 fs-13">Query Info</h6>
            </div>
            <div class="card-body py-2">
                <table class="table table-borderless table-sm mb-0 fs-12">
                    <tr>
                        <td class="text-muted fw-medium">Domain</td>
                        <td>
                            <span class="badge bg-{{ $query->domain->color ?? 'secondary' }}-subtle text-{{ $query->domain->color ?? 'secondary' }} fs-10">
                                {{ $query->domain->display_name }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">Status</td>
                        <td>
                            @php $sc = ['pending' => 'warning', 'processing' => 'info', 'completed' => 'success', 'failed' => 'danger']; @endphp
                            <span class="badge bg-{{ $sc[$query->status] ?? 'secondary' }}-subtle text-{{ $sc[$query->status] ?? 'secondary' }} fs-10">
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
                            <span class="badge bg-{{ $modelUsed === 'complex' ? 'warning' : 'info' }}-subtle text-{{ $modelUsed === 'complex' ? 'warning' : 'info' }} fs-10">
                                {{ $modelUsed === 'complex' ? 'GPT-4.1' : 'GPT-4.1-mini' }}
                            </span>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Safety Scores Summary --}}
        @if ($query->composite_safety_score !== null)
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="card-title mb-0 fs-13">
                        <iconify-icon icon="iconamoon:shield-yes-duotone" class="text-danger me-1"></iconify-icon>
                        Safety Overview
                    </h6>
                </div>
                <div class="card-body py-2">
                    @php $pct = $query->composite_safety_score * 100; @endphp
                    <div class="text-center mb-2">
                        <h3 class="fw-bold mb-0 text-{{ $pct >= 75 ? 'success' : ($pct >= 45 ? 'warning' : 'danger') }}">
                            {{ number_format($pct, 1) }}%
                        </h3>
                        <span class="text-muted fs-11">Composite Safety</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-{{ $pct >= 75 ? 'success' : ($pct >= 45 ? 'warning' : 'danger') }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                    @php
                        $sidebarRings = [
                            ['label' => 'Groundedness', 'value' => $query->groundedness_score, 'color' => 'primary'],
                            ['label' => 'LettuceDetect', 'value' => $query->lettuce_score, 'color' => 'success'],
                            ['label' => 'Confidence', 'value' => $query->confidence_score, 'color' => 'info'],
                        ];
                    @endphp
                    @foreach ($sidebarRings as $ring)
                        @if ($ring['value'] !== null)
                            <div class="d-flex justify-content-between fs-11 mb-1">
                                <span class="text-muted">{{ $ring['label'] }}</span>
                                <span class="fw-medium">{{ number_format($ring['value'] * 100, 1) }}%</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Pipeline Steps --}}
        @if ($query->agentRuns->count())
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="card-title mb-0 fs-13">
                        <iconify-icon icon="iconamoon:settings-duotone" class="text-warning me-1"></iconify-icon>
                        Pipeline Steps
                    </h6>
                </div>
                <div class="card-body py-2">
                    @foreach ($query->agentRuns->sortBy('created_at') as $run)
                        <div class="d-flex align-items-center gap-2 py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                            @php $statusColors = ['completed' => 'success', 'failed' => 'danger', 'running' => 'info']; @endphp
                            <div class="avatar-xs rounded-circle bg-{{ $statusColors[$run->status] ?? 'secondary' }} d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                <i class="bx {{ $run->status === 'completed' ? 'bx-check' : ($run->status === 'failed' ? 'bx-x' : 'bx-loader-alt bx-spin') }} text-white fs-12"></i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="fw-medium fs-12">{{ ucfirst(str_replace('_', ' ', $run->agent_type)) }}</span>
                            </div>
                            @if ($run->latency_ms)
                                <span class="text-muted fs-10">{{ number_format($run->latency_ms) }}ms</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Safety Level Legend --}}
        <div class="card">
            <div class="card-header py-2">
                <h6 class="card-title mb-0 fs-13">Safety Levels</h6>
            </div>
            <div class="card-body py-2">
                <div class="d-flex flex-column gap-1 fs-12">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success fs-10">Green</span>
                        <span class="text-muted">&ge; 75% &mdash; Fully grounded</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning fs-10">Yellow</span>
                        <span class="text-muted">45-74% &mdash; Review recommended</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger fs-10">Red</span>
                        <span class="text-muted">&lt; 45% &mdash; Blocked</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Pipeline Loading Overlay --}}
<div id="pipeline-overlay" class="d-none position-fixed top-0 start-0 w-100 h-100" style="z-index: 9999; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
    <div class="d-flex align-items-center justify-content-center h-100">
        <div class="card shadow-lg" style="max-width: 520px; width: 90%;">
            <div class="card-body p-4 text-center">
                <div class="typing-indicator mb-3">
                    <span></span><span></span><span></span>
                </div>
                <h5 class="fw-semibold mb-1">Processing Your Query</h5>
                <p class="text-muted fs-13 mb-3">Running the multi-agent RAG pipeline...</p>
                <div id="pipeline-status-text" class="text-muted fs-13">
                    <i class="bx bx-loader-alt bx-spin me-1"></i> Initializing pipeline...
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Scroll to the top of the last bot response so the answer is visible, not buried in sources
    var container = document.getElementById('chatContainer');
    if (container) {
        var botBubbles = container.querySelectorAll('.chat-bubble-bot');
        if (botBubbles.length > 0) {
            var lastBot = botBubbles[botBubbles.length - 1];
            // Scroll so the last bot bubble top is ~16px below the container top
            container.scrollTop = lastBot.offsetTop - container.offsetTop - 16;
        } else {
            container.scrollTop = container.scrollHeight;
        }
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

    // Pipeline overlay on form submit
    var chatForm = document.getElementById('chatForm');
    if (chatForm) {
        chatForm.addEventListener('submit', function () {
            var overlay = document.getElementById('pipeline-overlay');
            if (overlay) overlay.classList.remove('d-none');

            var steps = [
                { label: 'Screening content safety...', delay: 800 },
                { label: 'Retrieving relevant documents...', delay: 3000 },
                { label: 'Generating grounded answer...', delay: 6500 },
                { label: 'Running hallucination defense...', delay: 10000 },
            ];
            var statusText = document.getElementById('pipeline-status-text');
            steps.forEach(function (step) {
                setTimeout(function () {
                    if (statusText) statusText.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> ' + step.label;
                }, step.delay);
            });
        });
    }

    // Web Verify buttons — extract key claims from answer to build a better search query
    document.querySelectorAll('.web-verify-btn').forEach(function (btn) {
        var loaded = false;
        btn.addEventListener('click', function () {
            var targetId = btn.getAttribute('data-target');
            var panel = document.getElementById(targetId);
            var resultsContainer = document.getElementById(targetId.replace('websearch-', 'websearch-results-'));

            // Toggle collapse
            if (panel) {
                var bsCollapse = bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false });
                bsCollapse.toggle();
            }

            if (loaded) return;
            loaded = true;

            // Build a focused search query to cross-reference the AI answer against the web
            var question = btn.getAttribute('data-query') || '';
            var answer = btn.getAttribute('data-answer') || '';

            // Detect if the answer says "no information found" / negative response
            var negativePatterns = /do(es)? not contain|no information|cannot be confirmed|not (found|provided|mentioned|available|stated)/i;
            var isNegativeAnswer = negativePatterns.test(answer);

            var stopWords = new Set(['the','a','an','is','was','were','are','been','be','have','has','had','do','does','did','will','would','could','should','may','might','shall','can','need','about','above','after','again','against','all','also','and','any','because','before','being','between','both','but','by','each','few','for','from','further','get','got','here','how','into','its','just','more','most','not','now','only','other','our','out','over','own','same','she','some','such','than','that','their','them','then','there','these','they','this','those','through','too','under','until','very','what','when','where','which','while','who','whom','why','with','you','your','tell','told','know','said','says','like','make','made','take','come','want','look','use','find','give','many','well','back','way','new','one','two','provided','documents','contain','information','source','sources','according','stated','mentioned','therefore','confirmed','whether','cannot','given','document']);

            // If negative answer, search based on the QUESTION instead
            var textToSearch = isNegativeAnswer ? question : answer;

            var words = textToSearch.replace(/[^\w\s'-]/g, ' ').split(/\s+/)
                .filter(function (w) { return w.length > 3 && !stopWords.has(w.toLowerCase()); });

            // Prioritize proper nouns (capitalized) and longer terms
            var properNouns = words.filter(function (w) { return /^[A-Z]/.test(w); });
            var keyTerms = words.filter(function (w) { return w.length > 5; });

            var uniqueTerms = [];
            var seen = {};
            [].concat(properNouns, keyTerms, words).forEach(function (w) {
                var low = w.toLowerCase();
                if (!seen[low]) { seen[low] = true; uniqueTerms.push(w); }
            });
            var searchQuery = uniqueTerms.slice(0, 8).join(' ');

            // Fallback: if we extracted nothing useful, use the question as-is
            if (!searchQuery || searchQuery.length < 5) {
                searchQuery = question;
            }

            fetch('{{ route("api.web-search") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ query: searchQuery })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!resultsContainer) return;
                if (!data.success || !data.results || data.results.length === 0) {
                    resultsContainer.innerHTML = '<p class="text-muted fs-11 mb-0">No web results found.</p>';
                    return;
                }

                // Build verdict: check overlap of meaningful terms between answer and web results
                var stopWordsSet = new Set(['the','and','that','this','with','from','have','been','were','are','was','for','not','but','what','all','can','had','her','one','our','out','you','about','which','their','will','each','make','like','just','over','such','more','also','into','some','than','them','other','would','after','these','could','only']);
                var answerTerms = answer.toLowerCase().replace(/[^\w\s'-]/g, ' ').split(/\s+/)
                    .filter(function (w) { return w.length > 4 && !stopWordsSet.has(w); });
                var uniqueAnswerTerms = Array.from(new Set(answerTerms));
                var matchCount = 0;
                data.results.forEach(function (r) {
                    var text = ((r.title || '') + ' ' + (r.snippet || '')).toLowerCase();
                    var overlap = uniqueAnswerTerms.filter(function (w) { return text.indexOf(w) !== -1; }).length;
                    // Require at least 3 term matches for a meaningful overlap
                    if (overlap >= 3) matchCount++;
                });
                var verdictPct = data.results.length > 0 ? Math.round((matchCount / data.results.length) * 100) : 0;
                var verdictClass = verdictPct >= 60 ? 'success' : (verdictPct >= 30 ? 'warning' : 'danger');
                var verdictLabel = verdictPct >= 60 ? 'Likely Supported' : (verdictPct >= 30 ? 'Partially Supported' : 'Limited Web Support');

                var html = '';
                // Verdict summary
                html += '<div class="alert alert-' + verdictClass + ' py-2 px-3 mb-2 fs-12 d-flex align-items-center gap-2">';
                html += '<i class="bx bx-' + (verdictPct >= 60 ? 'check-circle' : (verdictPct >= 30 ? 'info-circle' : 'error-circle')) + ' fs-16"></i>';
                html += '<div><strong>' + verdictLabel + '</strong> — ' + matchCount + ' of ' + data.results.length + ' web sources contain terms matching the AI answer.</div>';
                html += '</div>';

                if (data.mock) {
                    html += '<div class="alert alert-info py-1 px-2 mb-2 fs-10"><i class="bx bx-info-circle me-1"></i>Mock results — configure <code>BING_SEARCH_API_KEY</code> in .env for live web search.</div>';
                }
                data.results.forEach(function (r) {
                    html += '<div class="border-bottom py-2">';
                    html += '<a href="' + r.url + '" target="_blank" rel="noopener" class="fw-medium fs-12 text-decoration-none d-block">';
                    html += '<i class="bx bx-link-external text-info me-1"></i>';
                    html += r.title;
                    html += '</a>';
                    html += '<p class="text-muted fs-11 mb-0 mt-1">' + r.snippet + '</p>';
                    html += '</div>';
                });
                html += '<div class="text-muted fs-10 mt-2"><i class="bx bx-search me-1"></i>' + data.results.length + ' result(s) &middot; ' + (data.latency_ms || 0) + 'ms</div>';
                resultsContainer.innerHTML = html;
            })
            .catch(function () {
                if (resultsContainer) {
                    resultsContainer.innerHTML = '<p class="text-danger fs-11 mb-0"><i class="bx bx-error me-1"></i>Failed to fetch web results.</p>';
                }
            });
        });
    });

    // Voice input for chat
    var micBtn = document.getElementById('chat-mic-btn');
    var chatInput = document.getElementById('chatInput');
    if (micBtn && chatInput && ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
        var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        var recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = true;
        recognition.lang = 'en-US';
        var recognizing = false;

        recognition.onstart = function () {
            recognizing = true;
            micBtn.classList.remove('btn-light');
            micBtn.classList.add('btn-danger');
        };
        recognition.onresult = function (e) {
            var transcript = '';
            for (var i = e.resultIndex; i < e.results.length; i++) {
                transcript += e.results[i][0].transcript;
            }
            chatInput.value = transcript;
        };
        recognition.onend = function () {
            recognizing = false;
            micBtn.classList.remove('btn-danger');
            micBtn.classList.add('btn-light');
        };
        micBtn.addEventListener('click', function () {
            if (recognizing) recognition.stop();
            else recognition.start();
        });
    }

    // Panel ordering: move opened panel to top of its container
    document.querySelectorAll('.details-panels-container .collapse').forEach(function (panel) {
        panel.addEventListener('shown.bs.collapse', function () {
            var container = panel.closest('.details-panels-container');
            if (container && container.firstElementChild !== panel) {
                container.insertBefore(panel, container.firstElementChild);
            }
            // Highlight the corresponding button
            var targetId = '#' + panel.id;
            var btn = document.querySelector('[data-bs-target="' + targetId + '"]');
            if (btn) btn.classList.add('active');
        });
        panel.addEventListener('hidden.bs.collapse', function () {
            var targetId = '#' + panel.id;
            var btn = document.querySelector('[data-bs-target="' + targetId + '"]');
            if (btn) btn.classList.remove('active');
        });
    });
});
</script>

{{-- Azure Speech TTS --}}
<script>
(function () {
    var activeSpeech = null;

    function stopSpeech() {
        if (!activeSpeech) return;
        window.speechSynthesis.cancel();
        resetBtn(activeSpeech.id);
        activeSpeech = null;
    }

    function resetBtn(id) {
        var btn = document.querySelector('.tts-btn[data-msg-id="' + id + '"]');
        if (btn) btn.innerHTML = '<iconify-icon icon="iconamoon:volume-up-duotone" class="fs-16 text-primary"></iconify-icon>';
    }

    function setPlayingBtn(id) {
        var btn = document.querySelector('.tts-btn[data-msg-id="' + id + '"]');
        if (btn) btn.innerHTML = '<i class="bx bx-stop-circle fs-16 text-danger"></i>';
    }

    function speakText(id, text) {
        if (!('speechSynthesis' in window)) return;
        var utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 1.05;
        utterance.pitch = 1;
        utterance.volume = 0.8;
        var voices = window.speechSynthesis.getVoices();
        var preferred = voices.find(function (v) {
            return v.lang.startsWith('en') && (v.name.indexOf('Natural') !== -1 || v.name.indexOf('Online') !== -1);
        }) || voices.find(function (v) { return v.lang.startsWith('en'); });
        if (preferred) utterance.voice = preferred;

        utterance.onstart = function () { setPlayingBtn(id); };
        utterance.onend = function () { activeSpeech = null; resetBtn(id); };
        utterance.onerror = function () { activeSpeech = null; resetBtn(id); };

        window.speechSynthesis.speak(utterance);
        activeSpeech = { id: id };
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.tts-btn');
        if (!btn) return;
        var id = btn.getAttribute('data-msg-id');
        var text = btn.getAttribute('data-text') || '';

        if (activeSpeech && activeSpeech.id === id) {
            stopSpeech();
            return;
        }

        stopSpeech();
        var speakT = text.length > 2000 ? text.substring(0, 2000) + '…' : text;
        speakText(id, speakT);
    });
})();
</script>
<style>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

{{-- vis.js VeriTrail interactive graph --}}
<script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
<script>
(function () {
    // Map node type/state to color
    function nodeColor(node) {
        var t = node.type || '';
        if (t === 'question')     return '#6366f1';
        if (t === 'gate')         return node.passed ? '#10b981' : '#ef4444';
        if (t === 'agent')        return '#6b7280';
        if (t === 'verification') return '#3b82f6';
        if (t === 'claim')        return (node.verdict === 'supported') ? '#10b981' : '#ef4444';
        if (t === 'source')       return '#f59e0b';
        if (t === 'answer')       return '#8b5cf6';
        return '#94a3b8';
    }

    function buildTooltip(node) {
        var lines = [];
        if (node.label) lines.push('<strong>' + node.label + '</strong>');
        if (node.type)  lines.push('Type: ' + node.type);
        if (node.score !== undefined && node.score !== null)
            lines.push('Score: ' + Math.round(node.score * 100) + '%');
        if (node.verdict) lines.push('Verdict: ' + node.verdict);
        if (node.latency_ms) lines.push('Latency: ' + node.latency_ms + 'ms');
        if (node.chunks_retrieved !== undefined) lines.push('Chunks: ' + node.chunks_retrieved);
        if (node.passed !== undefined) lines.push('Passed: ' + (node.passed ? 'Yes' : 'No'));
        return lines.join('<br>');
    }

    function initGraph(container, dagData) {
        if (!dagData || !dagData.nodes || !dagData.nodes.length) {
            container.innerHTML = '<div class="text-center text-muted py-4 fs-12">No graph data available</div>';
            return;
        }

        var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        var bodyBg = isDark ? '#212529' : '#ffffff';

        var visNodes = dagData.nodes.map(function (n) {
            var color = nodeColor(n);
            var tip = document.createElement('div');
            tip.innerHTML = buildTooltip(n);
            return {
                id: n.id,
                label: (n.label || n.id || '').substring(0, 20),
                title: tip,
                color: { background: bodyBg, border: color, highlight: { background: bodyBg, border: color } },
                font: { color: color, size: 11, face: 'Inter, system-ui, sans-serif' },
                borderWidth: 2,
                shape: n.type === 'question' ? 'ellipse' : (n.type === 'answer' ? 'diamond' : (n.type === 'claim' ? 'ellipse' : 'box')),
                size: n.type === 'answer' ? 20 : (n.type === 'question' ? 18 : 14),
                _raw: n
            };
        });

        var visEdges = (dagData.edges || []).map(function (e, i) {
            return {
                id: 'e' + i,
                from: e.from || e.source,
                to: e.to || e.target,
                label: e.label || '',
                arrows: 'to',
                arrowStrikethrough: false,
                color: { color: '#94a3b8', highlight: '#6366f1' },
                font: { size: 9, color: '#94a3b8', align: 'middle' },
                smooth: { type: 'straightCross', roundness: 0.2 }
            };
        });

        var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

        var options = {
            layout: {
                hierarchical: {
                    enabled: true,
                    direction: 'LR',
                    sortMethod: 'directed',
                    nodeSpacing: 100,
                    levelSeparation: 150
                }
            },
            physics: { enabled: false },
            interaction: {
                hover: true,
                tooltipDelay: 100,
                navigationButtons: false,
                zoomView: true
            },
            nodes: { borderWidth: 2 },
            edges: { width: 1.5 }
        };

        var network = new vis.Network(container, { nodes: new vis.DataSet(visNodes), edges: new vis.DataSet(visEdges) }, options);

        // Custom tooltip on click
        var tooltip = document.createElement('div');
        tooltip.className = 'vis-tooltip-custom';
        tooltip.style.display = 'none';
        document.body.appendChild(tooltip);

        network.on('click', function (params) {
            if (params.nodes.length > 0) {
                var nodeId = params.nodes[0];
                var nodeData = visNodes.find(function (n) { return n.id === nodeId; });
                if (nodeData) {
                    tooltip.innerHTML = buildTooltip(nodeData._raw);
                    tooltip.style.display = 'block';
                    tooltip.style.left = (params.event.srcEvent.clientX + 12) + 'px';
                    tooltip.style.top  = (params.event.srcEvent.clientY - 10) + 'px';
                }
            } else {
                tooltip.style.display = 'none';
            }
        });
        network.on('zoom', function () { tooltip.style.display = 'none'; });
        network.on('dragStart', function () { tooltip.style.display = 'none'; });
        document.addEventListener('click', function (e) {
            if (!container.contains(e.target)) tooltip.style.display = 'none';
        });
    }

    // Initialize when VeriTrail collapse panels open (lazy init)
    document.querySelectorAll('[id^="veritrial-"]').forEach(function (panel) {
        if (!panel.classList.contains('collapse')) return;
        var graphContainer = panel.querySelector('[id^="veritrial-graph-"]');
        if (!graphContainer) return;

        var initialized = false;
        panel.addEventListener('shown.bs.collapse', function () {
            if (initialized) return;
            initialized = true;
            try {
                var rawDag = graphContainer.getAttribute('data-dag');
                var dagData = rawDag ? JSON.parse(rawDag) : null;
                initGraph(graphContainer, dagData);
            } catch (e) {
                console.error('VeriTrail graph error:', e);
                graphContainer.innerHTML = '<div class="text-center text-muted py-4 fs-12">Failed to render graph: ' + e.message + '</div>';
            }
        });
    });
})();
</script>
@endpush

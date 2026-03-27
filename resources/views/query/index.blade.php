@extends('layouts.app')

@section('title', 'Ask Question')
@section('page-title', 'Ask Question')

@push('styles')
<style>
    .conversation-item { transition: all 0.2s ease; cursor: pointer; }
    .conversation-item:hover { background: var(--bs-light); transform: translateX(4px); }
    .conversation-item.active { border-left: 3px solid var(--bs-primary) !important; background: rgba(var(--bs-primary-rgb), 0.05); }
    .new-chat-btn { border: 2px dashed var(--bs-border-color); transition: all 0.2s; }
    .new-chat-btn:hover { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), 0.05); }
</style>
@endpush

@section('content')

<div class="row">
    {{-- Conversation Sidebar --}}
    <div class="col-lg-4">
        {{-- New Conversation --}}
        <div class="card new-chat-btn mb-2" data-bs-toggle="modal" data-bs-target="#newChatModal">
            <div class="card-body py-3 text-center">
                <iconify-icon icon="iconamoon:sign-plus-duotone" class="fs-20 text-primary me-1"></iconify-icon>
                <span class="fw-semibold text-primary">New Conversation</span>
            </div>
        </div>

        {{-- Search --}}
        <div class="card mb-2">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('query.index') }}">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-end-0">
                            <iconify-icon icon="iconamoon:search-duotone" class="text-muted"></iconify-icon>
                        </span>
                        <input type="text" class="form-control border-start-0" name="search"
                               value="{{ request('search') }}" placeholder="Search conversations...">
                    </div>
                </form>
            </div>
        </div>

        {{-- Conversation List --}}
        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fs-13">Conversations</h6>
                <span class="badge bg-primary-subtle text-primary rounded-pill fs-10">{{ $conversations->total() }}</span>
            </div>
            <div class="card-body p-0" style="max-height: 60vh; overflow-y: auto; overflow-x: hidden;">
                @forelse ($conversations as $conv)
                    @php $lastQuery = $conv->queries->first(); @endphp
                    <div class="conversation-item border-bottom px-3 py-2"
                         onclick="window.location='{{ $lastQuery ? route('query.show', $lastQuery) : '#' }}'">
                        <div class="d-flex align-items-start gap-2">
                            <div class="avatar-xs rounded-circle bg-{{ $conv->domain->color ?? 'primary' }}-subtle d-flex align-items-center justify-content-center flex-shrink-0 mt-1">
                                <iconify-icon icon="{{ $conv->domain->icon ?? 'iconamoon:comment-duotone' }}" class="text-{{ $conv->domain->color ?? 'primary' }} fs-14"></iconify-icon>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold fs-13 text-truncate">
                                    {{ $conv->title ?? 'Untitled Conversation' }}
                                </div>
                                @if ($lastQuery)
                                    <div class="text-muted fs-11 text-truncate">{{ Str::limit($lastQuery->question, 60) }}</div>
                                @endif
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-{{ $conv->domain->color ?? 'secondary' }}-subtle text-{{ $conv->domain->color ?? 'secondary' }} fs-10">{{ $conv->domain->display_name }}</span>
                                    <span class="text-muted fs-10">{{ $conv->last_activity_at?->diffForHumans() ?? $conv->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <iconify-icon icon="iconamoon:comment-dots-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                        <h6 class="fw-semibold mb-1">No conversations yet</h6>
                        <p class="text-muted fs-13 mb-0">Start a new conversation above</p>
                    </div>
                @endforelse
            </div>
            @if ($conversations->hasPages())
                <div class="card-footer py-2">
                    {{ $conversations->links() }}
                </div>
            @endif
        </div>

        {{-- Orphan queries (pre-conversation) --}}
        @if ($orphanQueries->count())
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="card-title mb-0 fs-13">Previous Queries</h6>
                </div>
                <div class="card-body p-0">
                    @foreach ($orphanQueries as $q)
                        <div class="conversation-item border-bottom px-3 py-2"
                             onclick="window.location='{{ route('query.show', $q) }}'">
                            <div class="text-truncate fs-13">{{ Str::limit($q->question, 50) }}</div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge bg-{{ $q->domain->color ?? 'secondary' }}-subtle text-{{ $q->domain->color ?? 'secondary' }} fs-10">{{ $q->domain->display_name ?? 'N/A' }}</span>
                                @if ($q->safety_level)
                                    @php $safetyColors = ['green' => 'success', 'yellow' => 'warning', 'red' => 'danger']; @endphp
                                    <span class="badge bg-{{ $safetyColors[$q->safety_level] ?? 'secondary' }}-subtle text-{{ $safetyColors[$q->safety_level] ?? 'secondary' }} fs-10">{{ ucfirst($q->safety_level) }}</span>
                                @endif
                                <span class="text-muted fs-10">{{ $q->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Main Content --}}
    <div class="col-lg-8">
        {{-- Specialization Policy Preview Card --}}
        @php $defaultDomain = $domains->first(); @endphp
        @if ($defaultDomain)
        <div class="card mb-3" id="specializationCard">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <iconify-icon id="specIcon" icon="{{ $defaultDomain->icon ?? 'iconamoon:category-duotone' }}" class="fs-20 text-primary"></iconify-icon>
                    <h6 class="fw-semibold mb-0 fs-14" id="specName">{{ $defaultDomain->display_name }}</h6>
                    @php
                        $thresh = $defaultDomain->safety_threshold ?? 0.75;
                        $threshBadge = $thresh >= 0.80 ? 'success' : ($thresh >= 0.70 ? 'warning' : 'orange');
                        $threshLabel = 'Green ≥' . number_format($thresh * 100, 0) . '%';
                    @endphp
                    <span class="badge rounded-pill ms-auto bg-{{ $threshBadge === 'orange' ? 'danger' : $threshBadge }}-subtle text-{{ $threshBadge === 'orange' ? 'danger' : $threshBadge }}" id="specThreshold">{{ $threshLabel }}</span>
                </div>
                <p class="text-muted fs-12 mb-1" id="specPrompt">{{ Str::limit($defaultDomain->system_prompt ?? 'No system prompt configured for this domain.', 150) }}</p>
                <div class="d-flex gap-3 fs-11 text-muted">
                    <span><iconify-icon icon="iconamoon:document-duotone" class="me-1"></iconify-icon><span id="specDocs">{{ $defaultDomain->documents->count() }}</span> documents</span>
                    <span><iconify-icon icon="iconamoon:shield-yes-duotone" class="me-1"></iconify-icon>Three-Ring Defense active</span>
                </div>
            </div>
        </div>
        @endif

        {{-- Welcome / Getting Started --}}
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="avatar-lg rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center mx-auto mb-3">
                    <iconify-icon icon="iconamoon:lightning-2-duotone" class="fs-36 text-primary"></iconify-icon>
                </div>
                <h4 class="fw-bold mb-2">Ask Axiomeer</h4>
                <p class="text-muted mb-4 mx-auto" style="max-width: 480px;">
                    Get grounded, cited answers from your knowledge base. Every response is verified through our three-ring hallucination defense.
                </p>
                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#newChatModal">
                    <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-1"></iconify-icon>
                    Start New Conversation
                </button>

                <div class="row mt-4 g-3 text-start mx-auto" style="max-width: 600px;">
                    @php
                        $steps = [
                            ['icon' => 'shield-yes-duotone', 'color' => 'warning', 'title' => 'Safety Screen', 'desc' => 'Input screened for harmful content'],
                            ['icon' => 'search-duotone', 'color' => 'info', 'title' => 'Retrieve', 'desc' => 'Relevant docs fetched from Azure AI Search'],
                            ['icon' => 'lightning-2-duotone', 'color' => 'primary', 'title' => 'Generate', 'desc' => 'Grounded answer with inline citations'],
                            ['icon' => 'check-circle-1-duotone', 'color' => 'success', 'title' => 'Verify', 'desc' => 'Three-ring hallucination defense'],
                        ];
                    @endphp
                    @foreach ($steps as $step)
                        <div class="col-6">
                            <div class="d-flex gap-2">
                                <div class="avatar-xs rounded bg-{{ $step['color'] }}-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                                    <iconify-icon icon="iconamoon:{{ $step['icon'] }}" class="text-{{ $step['color'] }}"></iconify-icon>
                                </div>
                                <div>
                                    <p class="fw-medium mb-0 fs-13">{{ $step['title'] }}</p>
                                    <p class="text-muted fs-11 mb-0">{{ $step['desc'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- New Chat Modal --}}
<div class="modal fade" id="newChatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('query.store') }}" id="newChatForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <iconify-icon icon="iconamoon:comment-duotone" class="text-primary me-1"></iconify-icon>
                        New Conversation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Domain Selector --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Domain</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($domains as $domain)
                                <div>
                                    <input type="radio" class="btn-check" name="domain_id"
                                           id="modal_domain_{{ $domain->id }}" value="{{ $domain->id }}"
                                           {{ $loop->first ? 'checked' : '' }}>
                                    <label class="btn btn-outline-{{ $domain->color ?? 'primary' }} btn-sm" for="modal_domain_{{ $domain->id }}">
                                        <iconify-icon icon="{{ $domain->icon }}" class="me-1"></iconify-icon>
                                        {{ $domain->display_name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Question --}}
                    <div class="mb-3">
                        <label for="modal_question" class="form-label fw-medium">Your Question</label>
                        <textarea class="form-control" id="modal_question" name="question" rows="3"
                                  placeholder="e.g. What are the key requirements for GDPR data processing agreements?" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <iconify-icon icon="iconamoon:send-duotone" class="me-1"></iconify-icon>
                        Send
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Pipeline Loading Overlay --}}
<div id="pipeline-overlay" class="d-none position-fixed top-0 start-0 w-100 h-100" style="z-index: 9999; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
    <div class="d-flex align-items-center justify-content-center h-100">
        <div class="card shadow-lg" style="max-width: 520px; width: 90%;">
            <div class="card-body p-4 text-center">
                <iconify-icon icon="iconamoon:lightning-2-duotone" class="fs-36 text-primary d-block mb-2"></iconify-icon>
                <h5 class="fw-semibold mb-1">Processing Your Query</h5>
                <p class="text-muted fs-13 mb-4">Running the multi-agent RAG pipeline...</p>

                <div class="d-flex align-items-center justify-content-between position-relative mb-4 px-2">
                    <div class="position-absolute" style="top: 20px; left: 50px; right: 50px; height: 3px; background: var(--bs-border-color); z-index: 0;">
                        <div id="pipeline-progress-line" style="height: 100%; width: 0%; background: var(--bs-primary); transition: width 0.8s ease;"></div>
                    </div>
                    @php $pSteps = ['safety' => 'Safety', 'retrieval' => 'Retrieval', 'generation' => 'Generation', 'verification' => 'Verification']; @endphp
                    @foreach ($pSteps as $sid => $slabel)
                        <div class="text-center position-relative" style="z-index: 1; flex: 1;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 border border-2 border-secondary bg-body" style="width: 40px; height: 40px; transition: all 0.4s ease;" id="step-{{ $sid }}-circle">
                                <i class="bx bx-time-five text-secondary fs-18" id="step-{{ $sid }}-icon"></i>
                            </div>
                            <div class="fw-semibold fs-12">{{ $slabel }}</div>
                        </div>
                    @endforeach
                </div>

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
window.axiomeerDomains = @json($domains);
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Specialization card update logic
    var domains = window.axiomeerDomains || [];

    function updateSpecCard(domainId) {
        var domain = domains.find(function (d) { return d.id == domainId; });
        if (!domain) return;

        var card = document.getElementById('specializationCard');
        if (!card) return;

        document.getElementById('specIcon').setAttribute('icon', domain.icon || 'iconamoon:category-duotone');
        document.getElementById('specName').textContent = domain.display_name;

        var thresh = domain.safety_threshold != null ? parseFloat(domain.safety_threshold) : 0.75;
        var threshPct = Math.round(thresh * 100);
        var badgeClass, badgeText;
        if (thresh >= 0.80) {
            badgeClass = 'bg-success-subtle text-success';
        } else if (thresh >= 0.70) {
            badgeClass = 'bg-warning-subtle text-warning';
        } else {
            badgeClass = 'bg-danger-subtle text-danger';
        }
        badgeText = 'Green \u2265' + threshPct + '%';
        var specThresh = document.getElementById('specThreshold');
        specThresh.className = 'badge rounded-pill ms-auto ' + badgeClass;
        specThresh.textContent = badgeText;

        var prompt = domain.system_prompt || 'No system prompt configured for this domain.';
        document.getElementById('specPrompt').textContent = prompt.length > 150 ? prompt.substring(0, 150) + '...' : prompt;

        var docCount = domain.documents ? domain.documents.length : 0;
        document.getElementById('specDocs').textContent = docCount;
    }

    // Listen for domain radio changes in the new chat modal
    document.querySelectorAll('input[name="domain_id"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (this.checked) updateSpecCard(this.value);
        });
    });

    // Listen for topbar domain selector clicks (axiomeer-domain-item)
    document.querySelectorAll('.axiomeer-domain-item').forEach(function (item) {
        item.addEventListener('click', function () {
            var domainId = this.dataset.domainId;
            if (domainId) updateSpecCard(domainId);
        });
    });

    // Pipeline loading overlay on form submit
    document.querySelectorAll('form[action*="query"]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var overlay = document.getElementById('pipeline-overlay');
            if (!overlay) return;
            overlay.classList.remove('d-none');

            var steps = [
                { id: 'safety', label: 'Screening content safety...', delay: 800 },
                { id: 'retrieval', label: 'Retrieving relevant documents...', delay: 3000 },
                { id: 'generation', label: 'Generating grounded answer...', delay: 6500 },
                { id: 'verification', label: 'Running hallucination defense...', delay: 10000 },
            ];

            var progressLine = document.getElementById('pipeline-progress-line');
            var statusText = document.getElementById('pipeline-status-text');

            steps.forEach(function (step, i) {
                setTimeout(function () {
                    var circle = document.getElementById('step-' + step.id + '-circle');
                    var icon = document.getElementById('step-' + step.id + '-icon');
                    circle.classList.remove('border-secondary', 'bg-body');
                    circle.classList.add('border-primary', 'bg-primary');
                    icon.className = 'bx bx-loader-alt bx-spin text-white fs-18';
                    statusText.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> ' + step.label;
                    progressLine.style.width = ((i + 1) * 25) + '%';

                    if (i > 0) {
                        var prev = steps[i - 1];
                        var prevCircle = document.getElementById('step-' + prev.id + '-circle');
                        var prevIcon = document.getElementById('step-' + prev.id + '-icon');
                        prevCircle.classList.remove('border-primary', 'bg-primary');
                        prevCircle.classList.add('border-success', 'bg-success');
                        prevIcon.className = 'bx bx-check text-white fs-18';
                    }
                }, step.delay);
            });
        });
    });
});
</script>
@endpush

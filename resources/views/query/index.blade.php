@extends('layouts.app')

@section('title', 'Ask Question')
@section('page-title', 'Ask Question')

@section('content')

<div class="row">
    {{-- Ask a Question Panel --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm rounded bg-primary-subtle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="iconamoon:comment-duotone" class="fs-20 text-primary"></iconify-icon>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Ask a Question</h5>
                        <p class="text-muted fs-12 mb-0">Get grounded, cited answers from your knowledge base</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('query.store') }}">
                    @csrf

                    {{-- Domain Selector --}}
                    <div class="mb-3">
                        <label for="domain_id" class="form-label fw-medium">Domain</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($domains as $domain)
                                <div>
                                    <input type="radio" class="btn-check" name="domain_id"
                                           id="domain_{{ $domain->id }}" value="{{ $domain->id }}"
                                           {{ old('domain_id', $domains->first()->id ?? '') == $domain->id ? 'checked' : '' }}>
                                    <label class="btn btn-outline-{{ $domain->color ?? 'primary' }} btn-sm" for="domain_{{ $domain->id }}">
                                        <iconify-icon icon="{{ $domain->icon }}" class="me-1"></iconify-icon>
                                        {{ $domain->display_name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('domain_id')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Question Input --}}
                    <div class="mb-3">
                        <label for="question" class="form-label fw-medium">Your Question</label>
                        <textarea class="form-control @error('question') is-invalid @enderror"
                                  id="question" name="question" rows="4"
                                  placeholder="e.g. What are the key requirements for GDPR data processing agreements?">{{ old('question') }}</textarea>
                        @error('question')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Ask a specific, compliance-related question. The system will retrieve relevant documents, generate a grounded answer, and verify it against hallucination defenses.</div>
                    </div>

                    {{-- Submit --}}
                    <div class="d-flex gap-2 align-items-center">
                        <button type="submit" class="btn btn-primary">
                            <iconify-icon icon="iconamoon:send-duotone" class="me-1"></iconify-icon>
                            Submit Question
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="mic-btn" title="Speak your question">
                            <iconify-icon icon="iconamoon:microphone-duotone" id="mic-icon"></iconify-icon>
                            <span id="mic-label" class="ms-1 d-none d-md-inline">Voice</span>
                        </button>
                        <span id="mic-status" class="text-muted fs-12 d-none">Listening...</span>
                    </div>
                </form>
            </div>
        </div>

        {{-- Query History --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Your Query History</h5>
                <span class="text-muted fs-12">{{ $queries->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Domain</th>
                                <th>Safety</th>
                                <th>Status</th>
                                <th style="width: 100px;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($queries as $q)
                                <tr class="cursor-pointer" onclick="window.location='{{ route('query.show', $q) }}'">
                                    <td>{{ Str::limit($q->question, 60) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $q->domain->color ?? 'secondary' }}-subtle text-{{ $q->domain->color ?? 'secondary' }}">
                                            {{ $q->domain->display_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($q->safety_level)
                                            @php
                                                $safetyColors = ['green' => 'success', 'yellow' => 'warning', 'red' => 'danger'];
                                            @endphp
                                            <span class="badge bg-{{ $safetyColors[$q->safety_level] ?? 'secondary' }}-subtle text-{{ $safetyColors[$q->safety_level] ?? 'secondary' }}">
                                                {{ ucfirst($q->safety_level) }}
                                            </span>
                                        @else
                                            <span class="text-muted fs-12">&mdash;</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $sc = ['pending' => 'warning', 'processing' => 'info', 'completed' => 'success', 'failed' => 'danger'];
                                        @endphp
                                        <span class="badge bg-{{ $sc[$q->status] ?? 'secondary' }}-subtle text-{{ $sc[$q->status] ?? 'secondary' }}">
                                            {{ ucfirst($q->status) }}
                                        </span>
                                    </td>
                                    <td class="text-muted fs-12">{{ $q->created_at->format('M d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="text-center py-4">
                                            <iconify-icon icon="iconamoon:comment-dots-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                                            <h6 class="fw-semibold mb-1">No queries yet</h6>
                                            <p class="text-muted fs-13 mb-0">Ask your first question above to get started.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($queries->hasPages())
            <div class="d-flex justify-content-center">
                {{ $queries->links() }}
            </div>
        @endif
    </div>

    {{-- Right Sidebar --}}
    <div class="col-lg-4">
        {{-- How It Works --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:lightning-2-duotone" class="text-warning me-1"></iconify-icon>
                    How It Works
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex gap-2">
                        <div class="avatar-xs rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                            <span class="fw-bold text-primary fs-12">1</span>
                        </div>
                        <div>
                            <p class="fw-medium mb-0 fs-14">Retrieve</p>
                            <p class="text-muted fs-12 mb-0">Relevant document chunks are retrieved from Azure AI Search</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="avatar-xs rounded-circle bg-info-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                            <span class="fw-bold text-info fs-12">2</span>
                        </div>
                        <div>
                            <p class="fw-medium mb-0 fs-14">Generate</p>
                            <p class="text-muted fs-12 mb-0">Azure OpenAI generates a grounded answer with citations</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="avatar-xs rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                            <span class="fw-bold text-warning fs-12">3</span>
                        </div>
                        <div>
                            <p class="fw-medium mb-0 fs-14">Verify</p>
                            <p class="text-muted fs-12 mb-0">Three-ring hallucination defense checks faithfulness</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="avatar-xs rounded-circle bg-success-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                            <span class="fw-bold text-success fs-12">4</span>
                        </div>
                        <div>
                            <p class="fw-medium mb-0 fs-14">Deliver</p>
                            <p class="text-muted fs-12 mb-0">Answer is returned with provenance trail and safety score</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Safety Legend --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:shield-yes-duotone" class="text-danger me-1"></iconify-icon>
                    Safety Levels
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success">Green</span>
                        <span class="fs-13 text-muted">High confidence, fully grounded</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning">Yellow</span>
                        <span class="fs-13 text-muted">Partial grounding, review recommended</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger">Red</span>
                        <span class="fs-13 text-muted">Hallucination detected, answer blocked</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const micBtn = document.getElementById('mic-btn');
    const micIcon = document.getElementById('mic-icon');
    const micStatus = document.getElementById('mic-status');
    const textarea = document.getElementById('question');

    if (!micBtn || !textarea) return;

    let recognizing = false;
    let recognition = null;

    // Try Web Speech API first (works in Chrome/Edge)
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'en-US';

        recognition.onstart = function () {
            recognizing = true;
            micBtn.classList.remove('btn-outline-secondary');
            micBtn.classList.add('btn-danger');
            micIcon.setAttribute('icon', 'iconamoon:microphone-duotone');
            micStatus.classList.remove('d-none');
            micStatus.textContent = 'Listening...';
        };

        recognition.onresult = function (event) {
            let finalTranscript = '';
            let interimTranscript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                if (event.results[i].isFinal) {
                    finalTranscript += event.results[i][0].transcript;
                } else {
                    interimTranscript += event.results[i][0].transcript;
                }
            }
            if (finalTranscript) {
                textarea.value += finalTranscript;
            }
            if (interimTranscript) {
                micStatus.textContent = interimTranscript;
            }
        };

        recognition.onerror = function (event) {
            console.error('Speech recognition error:', event.error);
            stopRecognition();
            if (event.error === 'not-allowed') {
                micStatus.textContent = 'Microphone access denied';
                micStatus.classList.remove('d-none');
            }
        };

        recognition.onend = function () {
            stopRecognition();
        };

        micBtn.addEventListener('click', function () {
            if (recognizing) {
                recognition.stop();
            } else {
                recognition.start();
            }
        });
    } else {
        // Fallback: use Azure Speech token endpoint
        micBtn.addEventListener('click', async function () {
            if (recognizing) return;

            try {
                micStatus.classList.remove('d-none');
                micStatus.textContent = 'Connecting to Azure Speech...';
                const resp = await fetch('{{ route("api.speech-token") }}');
                const data = await resp.json();

                if (data.error) {
                    micStatus.textContent = 'Speech not available: ' + data.error;
                    return;
                }

                micStatus.textContent = 'Azure Speech token obtained. Use a browser with Web Speech API (Chrome/Edge) for best results.';
            } catch (e) {
                micStatus.textContent = 'Speech service unavailable';
            }
        });
    }

    function stopRecognition() {
        recognizing = false;
        micBtn.classList.remove('btn-danger');
        micBtn.classList.add('btn-outline-secondary');
        micStatus.classList.add('d-none');
    }
});
</script>
@endpush

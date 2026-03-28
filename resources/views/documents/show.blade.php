@extends('layouts.app')

@section('title', $document->title)
@section('page-title', 'Documents')

@push('styles')
<style>
    .chunk-block { transition: background 0.25s ease; border-radius: 8px; padding: 10px 14px; }
    .chunk-block.tts-active { background: rgba(var(--bs-warning-rgb), 0.15); border-left: 3px solid var(--bs-warning); }
    .chunk-block:target { background: rgba(var(--bs-primary-rgb), 0.08); }
</style>
@endpush

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">{{ Str::limit($document->title, 60) }}</h4>
        <p class="text-muted mb-0 fs-13">{{ $document->original_filename }}</p>
    </div>
    <div class="col-auto d-flex gap-2">
        <a href="{{ route('documents.index') }}" class="btn btn-light">
            <i class="bx bx-arrow-back me-1"></i> Back
        </a>
        <form method="POST" action="{{ route('documents.destroy', $document) }}"
              onsubmit="return confirm('Delete this document? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="bx bx-trash me-1"></i> Delete
            </button>
        </form>
    </div>
</div>

<div class="row">
    {{-- Document Details --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Document Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted fw-medium" style="width: 160px;">Title</th>
                            <td>{{ $document->title }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-medium">Original File</th>
                            <td>{{ $document->original_filename }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-medium">Domain</th>
                            <td>
                                <span class="badge bg-{{ $document->domain->color ?? 'secondary' }}-subtle text-{{ $document->domain->color ?? 'secondary' }}">
                                    {{ $document->domain->display_name }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-medium">Status</th>
                            <td>
                                @php
                                    $statusColors = ['pending' => 'warning', 'indexing' => 'info', 'indexed' => 'success', 'failed' => 'danger'];
                                    $color = $statusColors[$document->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                    {{ ucfirst($document->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-medium">File Size</th>
                            <td>{{ number_format($document->file_size / 1024, 1) }} KB</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-medium">MIME Type</th>
                            <td><code>{{ $document->mime_type }}</code></td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-medium">Chunks</th>
                            <td>{{ $document->chunk_count ?: 'Not yet indexed' }}</td>
                        </tr>
                        @if ($document->index_name)
                        <tr>
                            <th class="text-muted fw-medium">Index</th>
                            <td><code>{{ $document->index_name }}</code></td>
                        </tr>
                        @endif
                        <tr>
                            <th class="text-muted fw-medium">Uploaded By</th>
                            <td>{{ $document->uploader->name ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-medium">Uploaded</th>
                            <td>{{ $document->created_at->format('M d, Y \a\t H:i') }} ({{ $document->created_at->diffForHumans() }})</td>
                        </tr>
                        @if ($document->indexed_at)
                        <tr>
                            <th class="text-muted fw-medium">Indexed</th>
                            <td>{{ $document->indexed_at->format('M d, Y \a\t H:i') }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Citations Sidebar --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Citations</h5>
            </div>
            <div class="card-body">
                @if ($document->citations->count())
                    <p class="text-muted fs-13 mb-2">This document has been cited {{ $document->citations->count() }} time(s) in query responses.</p>
                    <ul class="list-group list-group-flush">
                        @foreach ($document->citations->take(10) as $citation)
                            <li class="list-group-item px-0">
                                <div class="fw-medium fs-13">{{ Str::limit($citation->relatedQuery->question ?? 'Query', 60) }}</div>
                                <small class="text-muted">
                                    Relevance: {{ number_format(min($citation->relevance_score, 1.0) * 100, 0) }}%
                                    @if ($citation->page_number)
                                        &middot; Page {{ $citation->page_number }}
                                    @endif
                                </small>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center py-3">
                        <iconify-icon icon="iconamoon:link-chain-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                        <p class="text-muted fs-13 mb-0">No citations yet. This document hasn't been referenced in any query responses.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Document Content Viewer --}}
@if ($document->status === 'indexed' && count($chunks) > 0)
<div class="row mt-3">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:file-document-duotone" class="text-primary me-1"></iconify-icon>
                    Document Content
                    <span class="badge bg-primary-subtle text-primary ms-2 fs-11">{{ count($chunks) }} chunks</span>
                </h5>
                <button class="btn btn-sm btn-outline-success" id="docReadAloudBtn">
                    <iconify-icon icon="iconamoon:volume-up-duotone" class="me-1"></iconify-icon>
                    Read Aloud
                </button>
            </div>
            <div class="card-body" style="max-height: 600px; overflow-y: auto;" id="docContentBody">
                @foreach ($chunks as $chunk)
                    <div class="chunk-block mb-2"
                         id="chunk-{{ $chunk['chunk_index'] }}"
                         data-chunk-index="{{ $chunk['chunk_index'] }}">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="text-muted fs-10 fw-medium">
                                Chunk {{ $chunk['chunk_index'] + 1 }}
                                @if ($chunk['page'])
                                    &middot; Page {{ $chunk['page'] }}
                                @endif
                            </span>
                        </div>
                        <p class="mb-0 fs-13" style="white-space: pre-wrap; line-height: 1.7;">{{ $chunk['content'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@elseif ($document->status !== 'indexed')
<div class="row mt-3">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body text-center py-4">
                <iconify-icon icon="iconamoon:clock-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                <p class="text-muted fs-13 mb-0">Document content will be available once indexing is complete.</p>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    var chunks = @json(array_values($chunks));
    var currentChunkIndex = 0;
    var isSpeaking = false;
    var readBtn = document.getElementById('docReadAloudBtn');

    if (!readBtn || chunks.length === 0) return;

    function highlightChunk(idx) {
        document.querySelectorAll('.chunk-block').forEach(function (el) {
            el.classList.remove('tts-active');
        });
        var el = document.getElementById('chunk-' + idx);
        if (el) {
            el.classList.add('tts-active');
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function clearHighlights() {
        document.querySelectorAll('.chunk-block').forEach(function (el) {
            el.classList.remove('tts-active');
        });
    }

    function stopReading() {
        window.speechSynthesis.cancel();
        isSpeaking = false;
        currentChunkIndex = 0;
        clearHighlights();
        readBtn.innerHTML = '<iconify-icon icon="iconamoon:volume-up-duotone" class="me-1"></iconify-icon> Read Aloud';
    }

    function readChunk(idx) {
        if (idx >= chunks.length || !isSpeaking) {
            stopReading();
            return;
        }

        currentChunkIndex = idx;
        highlightChunk(chunks[idx].chunk_index);

        var utterance = new SpeechSynthesisUtterance(chunks[idx].content);
        utterance.rate = 1.0;
        utterance.pitch = 1;
        utterance.volume = 0.85;

        var voices = window.speechSynthesis.getVoices();
        var preferred = voices.find(function (v) {
            return v.lang.startsWith('en') && (v.name.indexOf('Natural') !== -1 || v.name.indexOf('Online') !== -1);
        }) || voices.find(function (v) { return v.lang.startsWith('en'); });
        if (preferred) utterance.voice = preferred;

        utterance.onend = function () {
            if (isSpeaking) readChunk(idx + 1);
        };
        utterance.onerror = function () {
            if (isSpeaking) readChunk(idx + 1);
        };

        window.speechSynthesis.speak(utterance);
    }

    readBtn.addEventListener('click', function () {
        if (isSpeaking) {
            stopReading();
            return;
        }

        if (!('speechSynthesis' in window)) {
            alert('Text-to-speech is not supported in your browser.');
            return;
        }

        isSpeaking = true;
        readBtn.innerHTML = '<i class="bx bx-stop-circle me-1"></i> Stop Reading';
        readChunk(0);
    });
})();
</script>
@endpush

@extends('layouts.app')

@section('title', $document->title)
@section('page-title', 'Documents')

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
                                <div class="fw-medium fs-13">{{ Str::limit($citation->query->question ?? 'Query', 60) }}</div>
                                <small class="text-muted">
                                    Relevance: {{ number_format($citation->relevance_score * 100, 0) }}%
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

@endsection

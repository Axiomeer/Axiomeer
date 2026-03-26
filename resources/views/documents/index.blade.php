@extends('layouts.app')

@section('title', 'Documents')
@section('page-title', 'Documents')

@section('content')

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Header --}}
<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">Document Library</h4>
        <p class="text-muted mb-0 fs-13">Upload and manage documents for RAG indexing</p>
    </div>
    <div class="col-auto">
        <a href="{{ route('documents.create') }}" class="btn btn-primary">
            <i class="bx bx-upload me-1"></i> Upload Documents
        </a>
    </div>
</div>

{{-- Domain Summary Cards --}}
<div class="row mb-3 g-2">
    @foreach ($domains as $domain)
        @php $domainDocs = $documents->where('domain_id', $domain->id); @endphp
        <div class="col-md-4 col-xl">
            <div class="card mb-0 {{ request('domain') == $domain->id ? 'border-' . ($domain->color ?? 'primary') : '' }}">
                <a href="{{ route('documents.index', ['domain' => $domain->id]) }}" class="card-body py-2 text-decoration-none d-flex align-items-center gap-2">
                    <div class="avatar-xs rounded bg-{{ $domain->color ?? 'primary' }}-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                        <iconify-icon icon="{{ $domain->icon }}" class="text-{{ $domain->color ?? 'primary' }}"></iconify-icon>
                    </div>
                    <div>
                        <span class="fw-semibold fs-13 text-dark">{{ $domain->display_name }}</span>
                        <span class="text-muted fs-11 d-block">{{ $domain->documents_count ?? 0 }} docs</span>
                    </div>
                </a>
            </div>
        </div>
    @endforeach
    <div class="col-md-4 col-xl">
        <div class="card mb-0 {{ !request('domain') ? 'border-secondary' : '' }}">
            <a href="{{ route('documents.index') }}" class="card-body py-2 text-decoration-none d-flex align-items-center gap-2">
                <div class="avatar-xs rounded bg-secondary-subtle d-flex align-items-center justify-content-center flex-shrink-0">
                    <iconify-icon icon="iconamoon:category-duotone" class="text-secondary"></iconify-icon>
                </div>
                <div>
                    <span class="fw-semibold fs-13 text-dark">All</span>
                    <span class="text-muted fs-11 d-block">{{ $documents->total() }} docs</span>
                </div>
            </a>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('documents.index') }}" class="row g-2 align-items-center">
                    <div class="col-auto flex-grow-1">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent">
                                <iconify-icon icon="iconamoon:search-duotone" class="text-muted"></iconify-icon>
                            </span>
                            <input type="text" class="form-control" name="search"
                                   value="{{ request('search') }}" placeholder="Search documents...">
                        </div>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" name="domain">
                            <option value="">All Domains</option>
                            @foreach ($domains as $domain)
                                <option value="{{ $domain->id }}" {{ request('domain') == $domain->id ? 'selected' : '' }}>
                                    {{ $domain->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="indexing" {{ request('status') === 'indexing' ? 'selected' : '' }}>Indexing</option>
                            <option value="indexed" {{ request('status') === 'indexed' ? 'selected' : '' }}>Indexed</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bx bx-filter-alt me-1"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Documents Table --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Domain</th>
                                <th>Status</th>
                                <th>Size</th>
                                <th>Chunks</th>
                                <th>Uploaded</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $doc)
                                <tr>
                                    <td>
                                        <a href="{{ route('documents.show', $doc) }}" class="fw-medium text-dark text-decoration-none">
                                            @php
                                                $ext = pathinfo($doc->original_filename ?? '', PATHINFO_EXTENSION);
                                                $fileIcons = ['pdf' => 'bx-file text-danger', 'docx' => 'bx-file text-primary', 'doc' => 'bx-file text-primary', 'xlsx' => 'bx-spreadsheet text-success', 'csv' => 'bx-spreadsheet text-success', 'jpg' => 'bx-image text-info', 'jpeg' => 'bx-image text-info', 'png' => 'bx-image text-info', 'txt' => 'bx-text text-muted'];
                                            @endphp
                                            <i class="bx {{ $fileIcons[$ext] ?? 'bx-file text-muted' }} me-1"></i>
                                            {{ Str::limit($doc->title, 50) }}
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $doc->original_filename }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $doc->domain->color ?? 'secondary' }}-subtle text-{{ $doc->domain->color ?? 'secondary' }}">
                                            {{ $doc->domain->display_name }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = ['pending' => 'warning', 'indexing' => 'info', 'indexed' => 'success', 'failed' => 'danger'];
                                            $color = $statusColors[$doc->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                            {{ ucfirst($doc->status) }}
                                        </span>
                                    </td>
                                    <td class="text-muted fs-13">{{ number_format($doc->file_size / 1024, 1) }} KB</td>
                                    <td class="text-muted fs-13">{{ $doc->chunk_count ?: '—' }}</td>
                                    <td class="text-muted fs-13">{{ $doc->created_at->diffForHumans() }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('documents.show', $doc) }}" class="btn btn-sm btn-light" title="View">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="d-inline"
                                              onsubmit="return confirm('Delete this document and its search index entries?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="text-center py-4">
                                            <iconify-icon icon="iconamoon:file-document-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                                            <h6 class="fw-semibold mb-1">No documents yet</h6>
                                            <p class="text-muted fs-13 mb-2">Upload your first document to start building the knowledge base.</p>
                                            <a href="{{ route('documents.create') }}" class="btn btn-sm btn-primary">
                                                <i class="bx bx-upload me-1"></i> Upload Documents
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($documents->hasPages())
                <div class="card-footer">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

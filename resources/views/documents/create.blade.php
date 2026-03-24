@extends('layouts.app')

@section('title', 'Upload Document')
@section('page-title', 'Documents')

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">Upload Document</h4>
        <p class="text-muted mb-0 fs-13">Add a new document to the knowledge base for RAG indexing</p>
    </div>
    <div class="col-auto">
        <a href="{{ route('documents.index') }}" class="btn btn-light">
            <i class="bx bx-arrow-back me-1"></i> Back to Documents
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Title --}}
                    <div class="mb-3">
                        <label for="title" class="form-label">Document Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title" value="{{ old('title') }}"
                               placeholder="e.g. GDPR Compliance Guidelines 2026">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Domain --}}
                    <div class="mb-3">
                        <label for="domain_id" class="form-label">Domain <span class="text-danger">*</span></label>
                        <select class="form-select @error('domain_id') is-invalid @enderror" id="domain_id" name="domain_id">
                            <option value="">Select a domain...</option>
                            @foreach ($domains as $domain)
                                <option value="{{ $domain->id }}" {{ old('domain_id') == $domain->id ? 'selected' : '' }}>
                                    {{ $domain->display_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('domain_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- File Upload --}}
                    <div class="mb-3">
                        <label for="file" class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('file') is-invalid @enderror"
                               id="file" name="file"
                               accept=".pdf,.doc,.docx,.txt,.csv,.json">
                        <div class="form-text">Accepted formats: PDF, DOC, DOCX, TXT, CSV, JSON. Max size: 50 MB.</div>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-upload me-1"></i> Upload Document
                        </button>
                        <a href="{{ route('documents.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Help Sidebar --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-2">
                    <i class="bx bx-info-circle text-primary me-1"></i> Upload Tips
                </h6>
                <ul class="text-muted fs-13 mb-0 ps-3">
                    <li class="mb-1">Choose the correct domain so the document is indexed with the right context.</li>
                    <li class="mb-1">PDF and DOCX files produce the best chunking results.</li>
                    <li class="mb-1">After upload, the document will be queued for indexing into Azure AI Search.</li>
                    <li>Large files may take a few minutes to process.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

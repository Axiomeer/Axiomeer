@extends('layouts.app')

@section('title', 'Upload Documents')
@section('page-title', 'Documents')

@push('styles')
<style>
    .dropzone {
        border: 2px dashed var(--bs-border-color);
        border-radius: 12px;
        padding: 40px 20px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background: var(--bs-body-bg);
    }
    .dropzone:hover, .dropzone.dragover {
        border-color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.05);
    }
    .dropzone.dragover {
        transform: scale(1.01);
    }
    .file-list-item {
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    .file-list-item .remove-btn { opacity: 0; transition: opacity 0.2s; }
    .file-list-item:hover .remove-btn { opacity: 1; }
</style>
@endpush

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">Upload Documents</h4>
        <p class="text-muted mb-0 fs-13">Add documents to the knowledge base for RAG indexing</p>
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
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" id="uploadForm">
                    @csrf

                    {{-- Domain --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Domain <span class="text-danger">*</span></label>
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

                    {{-- Drag & Drop Zone --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Files <span class="text-danger">*</span></label>
                        <div class="dropzone" id="dropzone">
                            <input type="file" id="fileInput" name="files[]" multiple class="d-none"
                                   accept=".pdf,.doc,.docx,.txt,.csv,.json,.jpg,.jpeg,.png,.bmp,.tiff,.heif,.xlsx,.pptx,.html">
                            <div id="dropzonePrompt">
                                <iconify-icon icon="iconamoon:cloud-upload-duotone" class="fs-48 text-primary d-block mb-2"></iconify-icon>
                                <h6 class="fw-semibold mb-1">Drag & drop files here</h6>
                                <p class="text-muted fs-13 mb-2">or click to browse</p>
                                <span class="badge bg-light text-muted fs-11">PDF, DOCX, TXT, CSV, JSON, Images, XLSX, PPTX, HTML</span>
                                <br>
                                <span class="text-muted fs-11">Max 50 MB per file &middot; Up to 10 files at once</span>
                            </div>
                        </div>
                        @error('files')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                        @error('files.*')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- File List --}}
                    <div id="fileList" class="mb-3 d-none">
                        <label class="form-label fw-medium">Selected Files</label>
                        <div id="fileListItems" class="d-flex flex-column gap-2"></div>
                    </div>

                    {{-- Submit --}}
                    <div class="d-flex gap-2 align-items-center">
                        <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
                            <i class="bx bx-upload me-1"></i>
                            <span id="uploadBtnText">Upload Documents</span>
                        </button>
                        <a href="{{ route('documents.index') }}" class="btn btn-light">Cancel</a>
                        <span id="fileCount" class="text-muted fs-12"></span>
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
                    <li class="mb-1">Select the correct domain so documents are indexed with proper context.</li>
                    <li class="mb-1">PDF and DOCX files produce the best chunking results.</li>
                    <li class="mb-1">You can upload up to <strong>10 files</strong> at once.</li>
                    <li class="mb-1">Each file is parsed by Azure Document Intelligence and chunked for indexing.</li>
                    <li class="mb-1">Large files may take a few minutes to process.</li>
                    <li>Drag multiple files or use Ctrl/Cmd+click to select several at once.</li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-2">
                    <iconify-icon icon="iconamoon:file-document-duotone" class="text-info me-1"></iconify-icon>
                    Supported Formats
                </h6>
                <div class="d-flex flex-wrap gap-1">
                    @foreach (['PDF', 'DOCX', 'XLSX', 'PPTX', 'TXT', 'CSV', 'JSON', 'HTML', 'JPG', 'PNG', 'BMP', 'TIFF'] as $fmt)
                        <span class="badge bg-light text-muted fs-11">.{{ strtolower($fmt) }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Processing Overlay --}}
<div id="upload-overlay" class="d-none position-fixed top-0 start-0 w-100 h-100" style="z-index: 9999; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
    <div class="d-flex align-items-center justify-content-center h-100">
        <div class="card shadow-lg" style="max-width: 420px; width: 90%;">
            <div class="card-body p-4 text-center">
                <i class="bx bx-loader-alt bx-spin fs-36 text-primary d-block mb-2"></i>
                <h5 class="fw-semibold mb-1">Processing Documents</h5>
                <p class="text-muted fs-13 mb-0">Parsing with Azure Document Intelligence and indexing to Azure AI Search. This may take a moment...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dropzone = document.getElementById('dropzone');
    var fileInput = document.getElementById('fileInput');
    var fileList = document.getElementById('fileList');
    var fileListItems = document.getElementById('fileListItems');
    var uploadBtn = document.getElementById('uploadBtn');
    var uploadBtnText = document.getElementById('uploadBtnText');
    var fileCount = document.getElementById('fileCount');
    var uploadForm = document.getElementById('uploadForm');

    var selectedFiles = new DataTransfer();

    // Click to browse
    dropzone.addEventListener('click', function () { fileInput.click(); });

    // Drag events
    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });
    dropzone.addEventListener('dragleave', function () {
        dropzone.classList.remove('dragover');
    });
    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        addFiles(e.dataTransfer.files);
    });

    // File input change
    fileInput.addEventListener('change', function () {
        addFiles(fileInput.files);
    });

    function addFiles(files) {
        var maxFiles = 10;
        var maxSize = 50 * 1024 * 1024; // 50MB

        for (var i = 0; i < files.length; i++) {
            if (selectedFiles.files.length >= maxFiles) {
                alert('Maximum ' + maxFiles + ' files allowed.');
                break;
            }
            if (files[i].size > maxSize) {
                alert(files[i].name + ' exceeds 50 MB limit.');
                continue;
            }
            selectedFiles.items.add(files[i]);
        }

        fileInput.files = selectedFiles.files;
        renderFileList();
    }

    function removeFile(index) {
        var newDt = new DataTransfer();
        for (var i = 0; i < selectedFiles.files.length; i++) {
            if (i !== index) newDt.items.add(selectedFiles.files[i]);
        }
        selectedFiles = newDt;
        fileInput.files = selectedFiles.files;
        renderFileList();
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function getFileIcon(name) {
        var ext = name.split('.').pop().toLowerCase();
        var icons = {
            pdf: 'bx-file text-danger',
            doc: 'bx-file text-primary', docx: 'bx-file text-primary',
            xlsx: 'bx-spreadsheet text-success', csv: 'bx-spreadsheet text-success',
            jpg: 'bx-image text-info', jpeg: 'bx-image text-info', png: 'bx-image text-info',
            txt: 'bx-text text-muted', json: 'bx-code-alt text-warning',
            html: 'bx-code text-danger', pptx: 'bx-slideshow text-warning',
        };
        return icons[ext] || 'bx-file text-muted';
    }

    function renderFileList() {
        fileListItems.innerHTML = '';
        var count = selectedFiles.files.length;

        if (count === 0) {
            fileList.classList.add('d-none');
            uploadBtn.disabled = true;
            fileCount.textContent = '';
            return;
        }

        fileList.classList.remove('d-none');
        uploadBtn.disabled = false;
        uploadBtnText.textContent = 'Upload ' + count + ' Document' + (count > 1 ? 's' : '');
        fileCount.textContent = count + ' file' + (count > 1 ? 's' : '') + ' selected';

        for (var i = 0; i < count; i++) {
            var f = selectedFiles.files[i];
            var div = document.createElement('div');
            div.className = 'file-list-item d-flex align-items-center justify-content-between border rounded px-3 py-2';
            div.innerHTML = '<div class="d-flex align-items-center gap-2">' +
                '<i class="bx ' + getFileIcon(f.name) + ' fs-18"></i>' +
                '<div><span class="fw-medium fs-13">' + f.name + '</span>' +
                '<span class="text-muted fs-11 ms-2">' + formatSize(f.size) + '</span></div></div>' +
                '<button type="button" class="btn btn-sm btn-link text-danger remove-btn p-0" data-index="' + i + '">' +
                '<i class="bx bx-x fs-18"></i></button>';
            fileListItems.appendChild(div);
        }

        // Bind remove buttons
        fileListItems.querySelectorAll('.remove-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                removeFile(parseInt(this.getAttribute('data-index')));
            });
        });
    }

    // Show overlay on submit
    uploadForm.addEventListener('submit', function () {
        document.getElementById('upload-overlay').classList.remove('d-none');
    });
});
</script>
@endpush

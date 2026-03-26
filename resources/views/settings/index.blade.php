@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">System Settings</h4>
        <p class="text-muted mb-0 fs-13">Azure service connections, domain configuration, and pipeline settings</p>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Azure Services Status --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <iconify-icon icon="iconamoon:cloud-duotone" class="text-primary me-1"></iconify-icon>
            Azure Service Connections
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach ($services as $service)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs rounded bg-{{ $service['color'] }}-subtle d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="{{ $service['icon'] }}" class="fs-18 text-{{ $service['color'] }}"></iconify-icon>
                                </div>
                                <span class="fw-semibold fs-14">{{ $service['name'] }}</span>
                            </div>
                            @if ($service['configured'])
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bx bx-check-circle me-1"></i>Connected
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">
                                    <i class="bx bx-minus-circle me-1"></i>Not configured
                                </span>
                            @endif
                        </div>
                        <p class="text-muted fs-12 mb-1">{{ $service['details'] }}</p>
                        @if ($service['endpoint'])
                            <code class="fs-11">{{ Str::limit($service['endpoint'], 50) }}</code>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row">
    {{-- Pipeline Configuration --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:settings-duotone" class="text-warning me-1"></iconify-icon>
                    Pipeline Configuration
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted fw-medium" style="width: 180px;">Model Router</td>
                            <td>
                                @if ($pipelineConfig['model_router'])
                                    <span class="badge bg-success-subtle text-success">Enabled</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Disabled</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-medium">Fast Model</td>
                            <td><code>{{ $pipelineConfig['fast_model'] }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-medium">Complex Model</td>
                            <td><code>{{ $pipelineConfig['complex_model'] }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-medium">Search Index</td>
                            <td><code>{{ $pipelineConfig['search_index'] }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-medium">Semantic Config</td>
                            <td><code>{{ $pipelineConfig['semantic_config'] }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-medium">API Version</td>
                            <td><code>{{ $pipelineConfig['api_version'] }}</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Hallucination Defense Config --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:shield-yes-duotone" class="text-danger me-1"></iconify-icon>
                    Three-Ring Hallucination Defense
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light">
                        <div>
                            <span class="fw-medium fs-14">Ring 1: Azure Groundedness API</span>
                            <p class="text-muted fs-12 mb-0">Source-level verification against retrieved documents</p>
                        </div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill">Weight: 50%</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light">
                        <div>
                            <span class="fw-medium fs-14">Ring 2: LettuceDetect</span>
                            <p class="text-muted fs-12 mb-0">Token-level hallucination detection via ModularRAG</p>
                        </div>
                        <span class="badge bg-success-subtle text-success rounded-pill">Weight: 30%</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light">
                        <div>
                            <span class="fw-medium fs-14">Ring 3: SRLM Confidence</span>
                            <p class="text-muted fs-12 mb-0">Uncertainty-aware reasoning with H-Neuron proxy</p>
                        </div>
                        <span class="badge bg-info-subtle text-info rounded-pill">Weight: 20%</span>
                    </div>
                </div>

                <div class="mt-3 p-2 border rounded">
                    <p class="fw-medium fs-13 mb-1">Safety Thresholds</p>
                    <div class="d-flex gap-3 fs-12">
                        <span><span class="badge bg-success">Green</span> &ge; 75%</span>
                        <span><span class="badge bg-warning">Yellow</span> 45% &ndash; 74%</span>
                        <span><span class="badge bg-danger">Red</span> &lt; 45%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Domains --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">
            <iconify-icon icon="iconamoon:category-duotone" class="text-info me-1"></iconify-icon>
            Domain Configuration
        </h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDomainModal">
            <i class="bx bx-plus me-1"></i> Add Domain
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Slug</th>
                        <th>Citation Format</th>
                        <th>Documents</th>
                        <th>Queries</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($domains as $domain)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <iconify-icon icon="{{ $domain->icon }}" class="fs-18 text-{{ $domain->color }}"></iconify-icon>
                                    <span class="fw-medium">{{ $domain->display_name }}</span>
                                </div>
                            </td>
                            <td><code>{{ $domain->slug }}</code></td>
                            <td>{{ $domain->citation_format ?? 'inline' }}</td>
                            <td>{{ $domain->documents_count }}</td>
                            <td>{{ $domain->queries_count }}</td>
                            <td>
                                @if ($domain->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editDomainModal-{{ $domain->id }}" title="Edit">
                                    <i class="bx bx-edit-alt"></i>
                                </button>
                                @if ($domain->documents_count == 0 && $domain->queries_count == 0)
                                    <form method="POST" action="{{ route('settings.domains.destroy', $domain) }}" class="d-inline"
                                          onsubmit="return confirm('Delete domain {{ $domain->display_name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- System Info --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">System Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted fw-medium" style="width: 160px;">Application</td>
                        <td>{{ config('app.name') }} v1.0</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">Environment</td>
                        <td><code>{{ config('app.env') }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">Laravel</td>
                        <td>{{ app()->version() }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">PHP</td>
                        <td>{{ PHP_VERSION }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted fw-medium" style="width: 160px;">Database</td>
                        <td>{{ config('database.default') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">Cache Driver</td>
                        <td>{{ config('cache.default') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">Queue Driver</td>
                        <td>{{ config('queue.default') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium">Debug Mode</td>
                        <td>
                            @if (config('app.debug'))
                                <span class="badge bg-warning-subtle text-warning">Enabled</span>
                            @else
                                <span class="badge bg-success-subtle text-success">Disabled</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Domain Modal --}}
<div class="modal fade" id="addDomainModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('settings.domains.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <iconify-icon icon="iconamoon:sign-plus-duotone" class="text-primary me-1"></iconify-icon>
                        Add Domain
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Display Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="display_name" required placeholder="e.g. Education">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Icon <span class="text-danger">*</span></label>
                        <select class="form-select" name="icon" required>
                            @foreach ($availableIcons as $icon)
                                <option value="{{ $icon }}">{{ str_replace(['iconamoon:', '-duotone'], '', $icon) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Color <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($availableColors as $color)
                                <div>
                                    <input type="radio" class="btn-check" name="color" id="add_color_{{ $color }}" value="{{ $color }}" {{ $loop->first ? 'checked' : '' }}>
                                    <label class="btn btn-sm btn-{{ $color }}" for="add_color_{{ $color }}">{{ ucfirst($color) }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Citation Format</label>
                        <select class="form-select" name="citation_format">
                            <option value="inline">Inline</option>
                            <option value="footnote">Footnote</option>
                            <option value="apa">APA</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-medium">System Prompt</label>
                        <textarea class="form-control" name="system_prompt" rows="3" placeholder="Custom instructions for this domain..."></textarea>
                        <div class="form-text">Optional prompt that guides how the LLM answers questions in this domain.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Create Domain
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Domain Modals --}}
@foreach ($domains as $domain)
    <div class="modal fade" id="editDomainModal-{{ $domain->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('settings.domains.update', $domain) }}">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <iconify-icon icon="iconamoon:edit-duotone" class="text-primary me-1"></iconify-icon>
                            Edit Domain: {{ $domain->display_name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Display Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="display_name" value="{{ $domain->display_name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Icon <span class="text-danger">*</span></label>
                            <select class="form-select" name="icon" required>
                                @foreach ($availableIcons as $icon)
                                    <option value="{{ $icon }}" {{ $domain->icon === $icon ? 'selected' : '' }}>
                                        {{ str_replace(['iconamoon:', '-duotone'], '', $icon) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Color <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($availableColors as $color)
                                    <div>
                                        <input type="radio" class="btn-check" name="color" id="edit_{{ $domain->id }}_color_{{ $color }}" value="{{ $color }}" {{ $domain->color === $color ? 'checked' : '' }}>
                                        <label class="btn btn-sm btn-{{ $color }}" for="edit_{{ $domain->id }}_color_{{ $color }}">{{ ucfirst($color) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Citation Format</label>
                            <select class="form-select" name="citation_format">
                                <option value="inline" {{ ($domain->citation_format ?? 'inline') === 'inline' ? 'selected' : '' }}>Inline</option>
                                <option value="footnote" {{ $domain->citation_format === 'footnote' ? 'selected' : '' }}>Footnote</option>
                                <option value="apa" {{ $domain->citation_format === 'apa' ? 'selected' : '' }}>APA</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">System Prompt</label>
                            <textarea class="form-control" name="system_prompt" rows="3">{{ $domain->system_prompt }}</textarea>
                        </div>
                        <div class="mb-0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="active_{{ $domain->id }}" value="1" {{ $domain->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="active_{{ $domain->id }}">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@endsection

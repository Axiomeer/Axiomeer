@extends('layouts.app')

@section('title', 'Audit Log')
@section('page-title', 'Audit Log')

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">Audit Log</h4>
        <p class="text-muted mb-0 fs-13">Complete trail of all system actions for compliance and governance</p>
    </div>
</div>

{{-- Filters --}}
<div class="card">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-12 mb-1">Search</label>
                <input type="text" class="form-control form-control-sm" name="search"
                       value="{{ request('search') }}" placeholder="Search descriptions...">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-12 mb-1">Action</label>
                <select class="form-select form-select-sm" name="action">
                    <option value="">All Actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                            {{ str_replace('_', ' ', ucfirst($action)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-12 mb-1">Severity</label>
                <select class="form-select form-select-sm" name="severity">
                    <option value="">All</option>
                    <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Info</option>
                    <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="error" {{ request('severity') === 'error' ? 'selected' : '' }}>Error</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="{{ route('audit-log') }}" class="btn btn-sm btn-light">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Log Table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 160px;">Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Severity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="fs-12 text-muted text-nowrap">{{ $log->created_at->format('M d H:i:s') }}</td>
                            <td>
                                <span class="fw-medium fs-13">{{ $log->user->name ?? 'System' }}</span>
                            </td>
                            <td>
                                <code class="fs-12">{{ $log->action }}</code>
                            </td>
                            <td class="fs-13">{{ Str::limit($log->description, 80) }}</td>
                            <td>
                                @php
                                    $sevColors = ['info' => 'info', 'warning' => 'warning', 'error' => 'danger'];
                                @endphp
                                <span class="badge bg-{{ $sevColors[$log->severity] ?? 'secondary' }}-subtle text-{{ $sevColors[$log->severity] ?? 'secondary' }}">
                                    {{ ucfirst($log->severity ?? 'info') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="text-center py-4">
                                    <iconify-icon icon="iconamoon:shield-yes-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                                    <h6 class="fw-semibold mb-1">No audit entries</h6>
                                    <p class="text-muted fs-13 mb-0">Actions will be logged here as the system processes queries.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($logs->hasPages())
    <div class="d-flex justify-content-center">
        {{ $logs->links() }}
    </div>
@endif

@endsection

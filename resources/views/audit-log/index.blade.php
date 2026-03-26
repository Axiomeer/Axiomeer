@extends('layouts.app')

@section('title', 'Audit Log')
@section('page-title', 'Audit Log')

@push('styles')
<style>
    .audit-detail-row { background: var(--bs-light); }
    .trace-badge { font-family: monospace; font-size: 10px; }
</style>
@endpush

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">Audit Log</h4>
        <p class="text-muted mb-0 fs-13">Complete trail of all system actions with OpenTelemetry tracing for compliance and governance</p>
    </div>
</div>

{{-- Filters --}}
<div class="card">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-12 mb-1">Search</label>
                <input type="text" class="form-control form-control-sm" name="search"
                       value="{{ request('search') }}" placeholder="Search descriptions, traces...">
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
                        <th>Agents</th>
                        <th>Severity</th>
                        <th style="width: 40px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="cursor-pointer" data-bs-toggle="collapse" data-bs-target="#detail-{{ $log->id }}">
                            <td class="fs-12 text-muted text-nowrap">{{ $log->created_at->format('M d H:i:s') }}</td>
                            <td>
                                <span class="fw-medium fs-13">{{ $log->user->name ?? 'System' }}</span>
                            </td>
                            <td>
                                <code class="fs-12">{{ $log->action }}</code>
                            </td>
                            <td class="fs-13">{{ Str::limit($log->description, 60) }}</td>
                            <td>
                                @if (is_array($log->details) && !empty($log->details['agents']))
                                    <div class="d-flex gap-1 flex-wrap">
                                        @foreach ($log->details['agents'] ?? [] as $agent)
                                            @php
                                                $agentColors = ['content_safety' => 'warning', 'retrieval' => 'info', 'generation' => 'primary', 'verification' => 'success'];
                                                $ac = $agentColors[$agent['type'] ?? ''] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $ac }}-subtle text-{{ $ac }} fs-10"
                                                  data-bs-toggle="tooltip"
                                                  title="{{ ucfirst(str_replace('_', ' ', $agent['type'] ?? '')) }}: {{ $agent['status'] ?? '' }} ({{ $agent['latency_ms'] ?? '?' }}ms)">
                                                {{ ucfirst(str_replace('_', ' ', Str::limit($agent['type'] ?? '', 8, ''))) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @elseif (is_array($log->details) && !empty($log->details['trace_id']))
                                    <code class="trace-badge text-muted">{{ Str::limit($log->details['trace_id'], 16) }}</code>
                                @else
                                    <span class="text-muted fs-11">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                @php $sevColors = ['info' => 'info', 'warning' => 'warning', 'error' => 'danger']; @endphp
                                <span class="badge bg-{{ $sevColors[$log->severity] ?? 'secondary' }}-subtle text-{{ $sevColors[$log->severity] ?? 'secondary' }}">
                                    {{ ucfirst($log->severity ?? 'info') }}
                                </span>
                            </td>
                            <td>
                                <i class="bx bx-chevron-down text-muted"></i>
                            </td>
                        </tr>
                        {{-- Expandable Detail Row --}}
                        <tr class="collapse audit-detail-row" id="detail-{{ $log->id }}">
                            <td colspan="7">
                                <div class="p-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="fw-medium fs-12 mb-1">Full Description</p>
                                            <p class="fs-12 text-muted mb-2">{{ $log->description }}</p>

                                            @if ($log->ip_address)
                                                <span class="fs-11 text-muted">
                                                    <i class="bx bx-globe me-1"></i>IP: {{ $log->ip_address }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            @if (is_array($log->details) && !empty($log->details))
                                                <p class="fw-medium fs-12 mb-1">Details</p>

                                                {{-- OTel Trace Info --}}
                                                @if (!empty($log->details['trace_id']))
                                                    <div class="mb-2">
                                                        <span class="fs-11 text-muted">
                                                            <iconify-icon icon="iconamoon:link-chain-duotone" class="me-1"></iconify-icon>
                                                            Trace: <code class="fs-10">{{ $log->details['trace_id'] }}</code>
                                                        </span>
                                                    </div>
                                                @endif

                                                {{-- Agent Pipeline Details --}}
                                                @if (!empty($log->details['agents']))
                                                    <div class="d-flex flex-column gap-1">
                                                        @foreach ($log->details['agents'] as $agent)
                                                            @php $ac = $agentColors[$agent['type'] ?? ''] ?? 'secondary'; @endphp
                                                            <div class="d-flex align-items-center justify-content-between border rounded px-2 py-1">
                                                                <div class="d-flex align-items-center gap-1">
                                                                    <i class="bx {{ ($agent['status'] ?? '') === 'completed' ? 'bx-check text-success' : 'bx-x text-danger' }} fs-14"></i>
                                                                    <span class="fw-medium fs-11">{{ ucfirst(str_replace('_', ' ', $agent['type'] ?? '')) }}</span>
                                                                </div>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    @if (!empty($agent['latency_ms']))
                                                                        <span class="text-muted fs-10">{{ $agent['latency_ms'] }}ms</span>
                                                                    @endif
                                                                    @if (!empty($agent['span_id']))
                                                                        <code class="fs-9">{{ Str::limit($agent['span_id'], 14) }}</code>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                {{-- Safety Level --}}
                                                @if (!empty($log->details['safety_level']))
                                                    <div class="mt-2">
                                                        @php $slColors = ['green' => 'success', 'yellow' => 'warning', 'red' => 'danger']; @endphp
                                                        <span class="badge bg-{{ $slColors[$log->details['safety_level']] ?? 'secondary' }}-subtle text-{{ $slColors[$log->details['safety_level']] ?? 'secondary' }}">
                                                            Safety: {{ ucfirst($log->details['safety_level']) }}
                                                        </span>
                                                        @if (!empty($log->details['composite_score']))
                                                            <span class="ms-1 fs-11 text-muted">({{ number_format($log->details['composite_score'] * 100, 1) }}%)</span>
                                                        @endif
                                                    </div>
                                                @endif

                                                {{-- Other details as JSON --}}
                                                @php
                                                    $otherDetails = collect($log->details)->except(['agents', 'trace_id', 'safety_level', 'composite_score'])->toArray();
                                                @endphp
                                                @if (!empty($otherDetails))
                                                    <details class="mt-2">
                                                        <summary class="fs-11 text-muted cursor-pointer">Raw details</summary>
                                                        <pre class="fs-10 mt-1 mb-0 p-2 bg-body rounded" style="max-height: 120px; overflow-y: auto;">{{ json_encode($otherDetails, JSON_PRETTY_PRINT) }}</pre>
                                                    </details>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
});
</script>
@endpush

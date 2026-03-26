@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Welcome Banner --}}
<div class="row">
    <div class="col-12">
        <div class="card overflow-hidden">
            <div class="card-body py-4">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">
                                <iconify-icon icon="iconamoon:shield-yes-duotone" class="me-1"></iconify-icon>
                                Grounded Knowledge Assistant
                            </span>
                        </div>
                        <h3 class="fw-bold mb-1">Welcome to Axiomeer</h3>
                        <p class="text-muted mb-0">Your governed RAG system for compliance-critical questions across legal, healthcare, and finance domains.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-primary-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:comment-duotone" class="fs-24 text-primary"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Total Queries</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($totalQueries) }}</h3>
                        <p class="text-muted mb-0 fs-12">{{ $totalQueries ? 'All time' : 'No queries yet' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-info-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:file-document-duotone" class="fs-24 text-info"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Documents Indexed</p>
                        <h3 class="mb-0 fw-bold">{{ $documentsIndexed }} / {{ $totalDocuments }}</h3>
                        <p class="text-muted mb-0 fs-12">{{ $totalDocuments ? 'Total uploaded' : 'Upload to get started' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-warning-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:trend-up-duotone" class="fs-24 text-warning"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Faithfulness Score</p>
                        <h3 class="mb-0 fw-bold">{{ $avgFaithfulness ? number_format($avgFaithfulness * 100, 1) . '%' : '&mdash;' }}</h3>
                        <p class="text-muted mb-0 fs-12">RAGAS metric</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar-sm rounded bg-danger-subtle d-flex align-items-center justify-content-center">
                            <iconify-icon icon="iconamoon:shield-yes-duotone" class="fs-24 text-danger"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-12">Hallucinations Blocked</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($hallucinationsBlocked) }}</h3>
                        <p class="text-muted mb-0 fs-12">Three-ring defense</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Two Column Layout --}}
<div class="row">
    {{-- Recent Queries --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm rounded bg-primary-subtle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="iconamoon:history-duotone" class="fs-20 text-primary"></iconify-icon>
                    </div>
                    <h5 class="card-title mb-0">Recent Queries</h5>
                </div>
                <span class="text-muted fs-12">Today</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Domain</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentQueries as $q)
                                <tr>
                                    <td>{{ Str::limit($q->question, 50) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $q->domain->color ?? 'secondary' }}-subtle text-{{ $q->domain->color ?? 'secondary' }}">
                                            {{ $q->domain->display_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $sc = ['pending' => 'warning', 'processing' => 'info', 'completed' => 'success', 'failed' => 'danger'];
                                        @endphp
                                        <span class="badge bg-{{ $sc[$q->status] ?? 'secondary' }}-subtle text-{{ $sc[$q->status] ?? 'secondary' }}">
                                            {{ ucfirst($q->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        <div class="text-center py-4">
                                            <iconify-icon icon="iconamoon:comment-dots-duotone" class="fs-36 text-muted d-block mb-2"></iconify-icon>
                                            <h6 class="fw-semibold mb-1">No queries yet</h6>
                                            <p class="text-muted fs-13 mb-0">Ask your first question to get started.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- System Status --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm rounded bg-info-subtle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="iconamoon:lightning-2-duotone" class="fs-20 text-info"></iconify-icon>
                    </div>
                    <h5 class="card-title mb-0">System Status</h5>
                </div>
                <span class="badge bg-success-subtle text-success rounded-pill">
                    <i class="bx bx-check-circle me-1"></i>Online
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    @php
                        $statusItems = [
                            ['key' => 'pipeline', 'label' => 'Agent Pipeline', 'icon' => 'iconamoon:lightning-2-duotone', 'color' => 'primary'],
                            ['key' => 'search', 'label' => 'Azure AI Search', 'icon' => 'iconamoon:search-duotone', 'color' => 'info'],
                            ['key' => 'safety', 'label' => 'Content Safety', 'icon' => 'iconamoon:shield-yes-duotone', 'color' => 'warning'],
                            ['key' => 'groundedness', 'label' => 'Foundry Groundedness', 'icon' => 'iconamoon:target-duotone', 'color' => 'danger'],
                        ];
                    @endphp
                    @foreach ($statusItems as $item)
                        <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <iconify-icon icon="{{ $item['icon'] }}" class="fs-20 text-{{ $item['color'] }}"></iconify-icon>
                                <span class="fw-medium fs-14">{{ $item['label'] }}</span>
                            </div>
                            @if ($serviceStatus[$item['key']] ?? false)
                                <span class="badge bg-success-subtle text-success rounded-pill">
                                    <i class="bx bx-check-circle me-1"></i>Connected
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill">Not configured</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@extends('layouts.app')

@section('title', 'Safety Test Suite')
@section('page-title', 'Safety Test Suite')

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">Synthetic Safety Test Suite</h4>
        <p class="text-muted mb-0 fs-13">
            Run adversarial prompts against Content Safety and Prompt Shields to measure defense effectiveness
        </p>
    </div>
</div>

{{-- Controls --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-medium fs-13">Test Category:</span>
                <select id="testCategory" class="form-select form-select-sm" style="width: 200px;">
                    <option value="all">All Categories (15 tests)</option>
                    <option value="jailbreak">Jailbreak Attempts (5)</option>
                    <option value="injection">Prompt Injection (3)</option>
                    <option value="harmful">Harmful Content (2)</option>
                    <option value="safe">Safe Queries (5)</option>
                </select>
            </div>
            <button class="btn btn-primary btn-sm" id="runTestsBtn">
                <iconify-icon icon="iconamoon:lightning-2-duotone" class="me-1"></iconify-icon>
                Run Test Suite
            </button>
            <div id="testProgress" class="d-none">
                <i class="bx bx-loader-alt bx-spin text-primary me-1"></i>
                <span class="text-muted fs-12" id="progressText">Running tests...</span>
            </div>
        </div>
    </div>
</div>

{{-- Summary Cards (hidden until results) --}}
<div class="row g-3 mb-3 d-none" id="summaryCards">
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1 fs-12">Detection Accuracy</p>
                        <h3 class="fw-bold mb-0" id="summaryAccuracy">—</h3>
                    </div>
                    <div class="avatar-sm rounded bg-primary-subtle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="iconamoon:target-duotone" class="text-primary fs-24"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1 fs-12">Tests Passed</p>
                        <h3 class="fw-bold mb-0 text-success" id="summaryPassed">—</h3>
                    </div>
                    <div class="avatar-sm rounded bg-success-subtle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="iconamoon:check-circle-1-duotone" class="text-success fs-24"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1 fs-12">Tests Failed</p>
                        <h3 class="fw-bold mb-0 text-danger" id="summaryFailed">—</h3>
                    </div>
                    <div class="avatar-sm rounded bg-danger-subtle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="iconamoon:sign-warning-duotone" class="text-danger fs-24"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1 fs-12">Avg Latency</p>
                        <h3 class="fw-bold mb-0" id="summaryLatency">—</h3>
                    </div>
                    <div class="avatar-sm rounded bg-info-subtle d-flex align-items-center justify-content-center">
                        <iconify-icon icon="iconamoon:lightning-2-duotone" class="text-info fs-24"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Results Table --}}
<div class="card d-none" id="resultsCard">
    <div class="card-header py-2">
        <h6 class="card-title mb-0 fs-13">
            <iconify-icon icon="iconamoon:file-document-duotone" class="text-warning me-1"></iconify-icon>
            Test Results
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 fs-12">
                <thead>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th>Prompt</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Expected</th>
                        <th class="text-center">Actual</th>
                        <th class="text-center">Content Safety</th>
                        <th class="text-center">Prompt Shields</th>
                        <th class="text-center">Latency</th>
                        <th class="text-center">Result</th>
                    </tr>
                </thead>
                <tbody id="resultsBody">
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Methodology --}}
<div class="card mt-3">
    <div class="card-header py-2">
        <h6 class="card-title mb-0 fs-13">
            <iconify-icon icon="iconamoon:certificate-badge-duotone" class="text-info me-1"></iconify-icon>
            Methodology
        </h6>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-3">
                <h6 class="fw-semibold fs-12">Jailbreak Tests</h6>
                <p class="text-muted fs-11 mb-0">Attempts to bypass system instructions using role-play, override commands, and persona hijacking. Expected: <strong>blocked</strong> by Prompt Shields.</p>
            </div>
            <div class="col-md-3">
                <h6 class="fw-semibold fs-12">Injection Tests</h6>
                <p class="text-muted fs-11 mb-0">Embeds hidden system instructions within user prompts to override behavior. Expected: <strong>blocked</strong> by Prompt Shields injection detection.</p>
            </div>
            <div class="col-md-3">
                <h6 class="fw-semibold fs-12">Harmful Content</h6>
                <p class="text-muted fs-11 mb-0">Tests Content Safety harm categories (hate, violence, sexual, self-harm). Expected: <strong>blocked</strong> when severity exceeds threshold.</p>
            </div>
            <div class="col-md-3">
                <h6 class="fw-semibold fs-12">Safe Queries</h6>
                <p class="text-muted fs-11 mb-0">Legitimate business questions that should pass all safety checks. Expected: <strong>passed</strong> — ensures no false positives.</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var runBtn = document.getElementById('runTestsBtn');
    var progress = document.getElementById('testProgress');
    var progressText = document.getElementById('progressText');
    var summaryCards = document.getElementById('summaryCards');
    var resultsCard = document.getElementById('resultsCard');
    var resultsBody = document.getElementById('resultsBody');
    var categorySelect = document.getElementById('testCategory');

    runBtn.addEventListener('click', function () {
        runBtn.disabled = true;
        progress.classList.remove('d-none');
        progressText.textContent = 'Running safety tests against Azure Content Safety...';

        fetch('{{ route("safety-test.run") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ category: categorySelect.value })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            progress.classList.add('d-none');
            runBtn.disabled = false;

            // Summary
            summaryCards.classList.remove('d-none');
            var s = data.summary;
            document.getElementById('summaryAccuracy').textContent = s.accuracy + '%';
            document.getElementById('summaryAccuracy').className = 'fw-bold mb-0 text-' + (s.accuracy >= 80 ? 'success' : (s.accuracy >= 50 ? 'warning' : 'danger'));
            document.getElementById('summaryPassed').textContent = s.passed + '/' + s.total;
            document.getElementById('summaryFailed').textContent = s.failed + '/' + s.total;
            document.getElementById('summaryLatency').textContent = s.avg_latency_ms + 'ms';

            // Results table
            resultsCard.classList.remove('d-none');
            resultsBody.innerHTML = '';

            data.results.forEach(function (r, i) {
                var typeColors = { jailbreak: 'danger', injection: 'warning', harmful: 'dark', safe: 'success' };
                var row = '<tr class="' + (r.correct ? '' : 'table-danger') + '">';
                row += '<td>' + (i + 1) + '</td>';
                row += '<td class="text-truncate" style="max-width: 300px;" title="' + escapeAttr(r.prompt) + '">' + escapeHtml(r.prompt) + '</td>';
                row += '<td class="text-center"><span class="badge bg-' + (typeColors[r.type] || 'secondary') + '-subtle text-' + (typeColors[r.type] || 'secondary') + ' fs-10">' + r.type + '</span></td>';
                row += '<td class="text-center"><span class="badge bg-' + (r.expected === 'blocked' ? 'danger' : 'success') + '-subtle text-' + (r.expected === 'blocked' ? 'danger' : 'success') + ' fs-10">' + r.expected + '</span></td>';
                row += '<td class="text-center"><span class="badge bg-' + (r.actual === 'blocked' ? 'danger' : 'success') + '-subtle text-' + (r.actual === 'blocked' ? 'danger' : 'success') + ' fs-10">' + r.actual + '</span></td>';
                row += '<td class="text-center">';
                if (r.content_safety.api_available) {
                    row += r.content_safety.blocked ? '<i class="bx bx-shield text-danger"></i>' : '<i class="bx bx-check text-success"></i>';
                } else {
                    row += '<span class="text-muted fs-10">N/A</span>';
                }
                row += '</td>';
                row += '<td class="text-center">';
                if (r.prompt_shield.api_available) {
                    if (r.prompt_shield.jailbreak) row += '<span class="badge bg-danger fs-9">Jailbreak</span>';
                    else if (r.prompt_shield.injection) row += '<span class="badge bg-warning fs-9">Injection</span>';
                    else row += '<i class="bx bx-check text-success"></i>';
                } else {
                    row += '<span class="text-muted fs-10">N/A</span>';
                }
                row += '</td>';
                row += '<td class="text-center text-muted">' + r.latency_ms + 'ms</td>';
                row += '<td class="text-center">';
                row += r.correct ? '<i class="bx bx-check-circle text-success fs-16"></i>' : '<i class="bx bx-x-circle text-danger fs-16"></i>';
                row += '</td>';
                row += '</tr>';
                resultsBody.innerHTML += row;
            });
        })
        .catch(function (err) {
            progress.classList.add('d-none');
            runBtn.disabled = false;
            alert('Error running tests: ' + err.message);
        });
    });

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }
    function escapeAttr(text) {
        return text.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
});
</script>
@endpush

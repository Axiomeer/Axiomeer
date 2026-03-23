@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="a-up" style="margin-bottom:2rem;">
    <div class="hero-pill" style="margin-bottom:12px;">
        <i class="ph ph-shield-check"></i>
        Grounded Knowledge Assistant
    </div>
    <h1 class="page-title">Welcome to Axiomeer</h1>
    <p class="page-sub" style="max-width:520px;">
        Your governed RAG system for compliance-critical questions across legal, healthcare, and finance domains.
    </p>
</div>

{{-- KPI Cards --}}
<div class="grid-4 a-up a-up-1" style="margin-bottom:1.5rem;">
    <div class="kpi-card">
        <div class="kpi-icon teal"><i class="ph ph-chat-centered-text"></i></div>
        <p class="kpi-label">Total Queries</p>
        <p class="kpi-value" style="color:var(--teal-d);">0</p>
        <p style="font-size:12px;color:var(--ink-4);margin-top:8px;font-family:var(--f-m);">No queries yet</p>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon blue"><i class="ph ph-files"></i></div>
        <p class="kpi-label">Documents Indexed</p>
        <p class="kpi-value" style="color:var(--blue-d);">0</p>
        <p style="font-size:12px;color:var(--ink-4);margin-top:8px;font-family:var(--f-m);">Upload to get started</p>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon amber"><i class="ph ph-chart-line-up"></i></div>
        <p class="kpi-label">Faithfulness Score</p>
        <p class="kpi-value" style="color:var(--amber-d);">&mdash;</p>
        <p style="font-size:12px;color:var(--ink-4);margin-top:8px;font-family:var(--f-m);">RAGAS metric</p>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon coral"><i class="ph ph-shield-warning"></i></div>
        <p class="kpi-label">Hallucinations Blocked</p>
        <p class="kpi-value" style="color:var(--coral-d);">0</p>
        <p style="font-size:12px;color:var(--ink-4);margin-top:8px;font-family:var(--f-m);">Three-ring defense</p>
    </div>
</div>

{{-- Two-column layout --}}
<div class="grid-2 a-up a-up-2">
    {{-- Recent Queries --}}
    <div class="card-mm" style="overflow:hidden;">
        <div class="card-header">
            <div class="card-header-title">
                <div class="card-header-icon teal"><i class="ph ph-clock-counter-clockwise"></i></div>
                <span class="card-header-text">Recent Queries</span>
            </div>
            <span class="card-header-meta">Today</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="tbl-mm">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Domain</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="3">
                            <div class="empty-state" style="padding:2rem;">
                                <div class="empty-icon" style="width:48px;height:48px;font-size:22px;">
                                    <i class="ph ph-chat-centered-dots"></i>
                                </div>
                                <p style="font-family:var(--f-d);font-size:14px;font-weight:700;margin-bottom:4px;">No queries yet</p>
                                <p style="font-size:12px;color:var(--ink-3);margin:0;">Ask your first question to get started.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- System Status --}}
    <div class="card-mm" style="overflow:hidden;">
        <div class="card-header">
            <div class="card-header-title">
                <div class="card-header-icon blue"><i class="ph ph-activity"></i></div>
                <span class="card-header-text">System Status</span>
            </div>
            <span class="badge-mm badge-teal"><i class="ph ph-check-circle" style="font-size:12px;"></i> Online</span>
        </div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:12px;">
                {{-- Agent Pipeline --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:var(--s1);border-radius:var(--r-md);border:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <i class="ph ph-robot" style="font-size:16px;color:var(--teal-d);"></i>
                        <span style="font-size:13px;font-weight:600;">Agent Pipeline</span>
                    </div>
                    <span class="badge-mm badge-gray">Not configured</span>
                </div>

                {{-- Azure AI Search --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:var(--s1);border-radius:var(--r-md);border:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <i class="ph ph-magnifying-glass" style="font-size:16px;color:var(--blue-d);"></i>
                        <span style="font-size:13px;font-weight:600;">Azure AI Search</span>
                    </div>
                    <span class="badge-mm badge-gray">Not configured</span>
                </div>

                {{-- Content Safety --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:var(--s1);border-radius:var(--r-md);border:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <i class="ph ph-shield-check" style="font-size:16px;color:var(--amber-d);"></i>
                        <span style="font-size:13px;font-weight:600;">Content Safety</span>
                    </div>
                    <span class="badge-mm badge-gray">Not configured</span>
                </div>

                {{-- Groundedness API --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:var(--s1);border-radius:var(--r-md);border:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <i class="ph ph-target" style="font-size:16px;color:var(--coral-d);"></i>
                        <span style="font-size:13px;font-weight:600;">Groundedness API</span>
                    </div>
                    <span class="badge-mm badge-gray">Not configured</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

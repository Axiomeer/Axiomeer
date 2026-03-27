{{-- Sidebar / Main Nav --}}
<div class="main-nav">
    {{-- Brand Logo --}}
    <div class="logo-box axiomeer-brand">
        <a href="{{ url('/') }}" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="axiomeer-logo-circle">
                <img src="{{ asset('images/logo.png') }}" alt="Axiomeer">
            </div>
            <span class="axiomeer-brand-text logo-lg" style="margin-top: -50px;">Axiomeer</span>
        </a>
    </div>

    {{-- Menu Toggle (sm-hover) --}}
    <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
        <iconify-icon icon="iconamoon:arrow-left-4-square-duotone" class="button-sm-hover-icon"></iconify-icon>
    </button>

    <div class="scrollbar" data-simplebar>
        <ul class="navbar-nav" id="navbar-nav">

            {{-- Main --}}
            <li class="menu-title">Main</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ url('/dashboard') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:home-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('query.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:comment-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text">Ask Question</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ url('/documents') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:file-document-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text">Documents</span>
                </a>
            </li>

            {{-- Analytics --}}
            <li class="menu-title">Analytics</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ url('/analytics') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:trend-up-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text">Performance</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ url('/audit-log') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:shield-yes-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text">Audit Log</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ url('/evaluation') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:certificate-badge-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text">RAGAS Metrics</span>
                </a>
            </li>

            {{-- Responsible AI --}}
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/responsible-ai') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text">Responsible AI</span>
                </a>
            </li>

            {{-- System --}}
            <li class="menu-title">System</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ url('/settings') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:settings-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text">Settings</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ url('/agents') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:lightning-2-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text">Agent Pipeline</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ url('/safety-test') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:shield-yes-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text">Safety Tests</span>
                </a>
            </li>
        </ul>
    </div>
</div>

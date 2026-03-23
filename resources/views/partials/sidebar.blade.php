<aside class="sidebar" id="sidebar">
    {{-- Logo --}}
    <div class="sidebar-header">
        <a href="{{ url('/') }}" class="sidebar-logo">
            <div class="sidebar-logo-mark">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M8 2L2 14h12L8 2z" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
                    <circle cx="8" cy="9" r="2" fill="white" fill-opacity="0.8"/>
                </svg>
            </div>
            <span class="sidebar-logo-text">Axiom<em>eer</em></span>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="sidebar-nav">
        {{-- Main --}}
        <div class="sidebar-section">
            <p class="sidebar-section-label">Main</p>

            <a href="{{ url('/dashboard') }}" class="sidebar-link {{ request()->is('dashboard') ? 'active' : '' }}">
                <i class="ph ph-squares-four"></i>
                <span>Dashboard</span>
            </a>

            <a href="{{ url('/query') }}" class="sidebar-link {{ request()->is('query*') ? 'active' : '' }}">
                <i class="ph ph-chat-centered-text"></i>
                <span>Ask Question</span>
            </a>

            <a href="{{ url('/documents') }}" class="sidebar-link {{ request()->is('documents*') ? 'active' : '' }}">
                <i class="ph ph-files"></i>
                <span>Documents</span>
            </a>
        </div>

        {{-- Analytics --}}
        <div class="sidebar-section">
            <p class="sidebar-section-label">Analytics</p>

            <a href="{{ url('/analytics') }}" class="sidebar-link {{ request()->is('analytics*') ? 'active' : '' }}">
                <i class="ph ph-chart-line-up"></i>
                <span>Performance</span>
            </a>

            <a href="{{ url('/audit-log') }}" class="sidebar-link {{ request()->is('audit-log*') ? 'active' : '' }}">
                <i class="ph ph-shield-check"></i>
                <span>Audit Log</span>
            </a>

            <a href="{{ url('/evaluation') }}" class="sidebar-link {{ request()->is('evaluation*') ? 'active' : '' }}">
                <i class="ph ph-exam"></i>
                <span>RAGAS Metrics</span>
            </a>
        </div>

        {{-- System --}}
        <div class="sidebar-section">
            <p class="sidebar-section-label">System</p>

            <a href="{{ url('/settings') }}" class="sidebar-link {{ request()->is('settings*') ? 'active' : '' }}">
                <i class="ph ph-gear"></i>
                <span>Settings</span>
            </a>

            <a href="{{ url('/agents') }}" class="sidebar-link {{ request()->is('agents*') ? 'active' : '' }}">
                <i class="ph ph-robot"></i>
                <span>Agent Pipeline</span>
            </a>
        </div>
    </nav>

    {{-- Sidebar footer --}}
    <div class="sidebar-footer">
        <a href="{{ url('/settings') }}" class="sidebar-link">
            <i class="ph ph-question"></i>
            <span>Help & Docs</span>
        </a>
    </div>
</aside>

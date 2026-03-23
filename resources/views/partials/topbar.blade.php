<header class="topbar">
    <div class="topbar-left">
        {{-- Sidebar toggle --}}
        <button class="topbar-toggle" id="sidebar-toggle" title="Toggle sidebar">
            <i class="ph ph-list"></i>
        </button>

        {{-- Page title --}}
        <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
    </div>

    <div class="topbar-right">
        {{-- Domain selector --}}
        <select class="mm-select" id="domain-selector" style="width:160px;padding:6px 30px 6px 10px;font-size:12px;">
            <option value="legal">Legal</option>
            <option value="healthcare">Healthcare</option>
            <option value="finance">Finance</option>
        </select>

        {{-- Theme toggle --}}
        <button class="theme-toggle" id="theme-toggle" title="Toggle dark mode">
            <i class="ph ph-moon"></i>
            <i class="ph ph-sun"></i>
        </button>

        {{-- Notifications --}}
        <button class="mm-icon-btn" id="notif-btn" title="Notifications">
            <i class="ph ph-bell" style="font-size:17px;"></i>
            <span class="mm-notif-pip" style="display:block;"></span>
        </button>

        {{-- Avatar --}}
        <div class="mm-avt" title="Profile">
            {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
        </div>
    </div>
</header>

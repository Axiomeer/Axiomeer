{{-- Topbar --}}
<header class="topbar">
    <div class="container-xxl">
        <div class="navbar-header">
            <div class="d-flex align-items-center gap-2">
                {{-- Menu Toggle --}}
                <div class="topbar-item">
                    <button type="button" class="button-toggle-menu">
                        <iconify-icon icon="iconamoon:menu-burger-horizontal" class="fs-22"></iconify-icon>
                    </button>
                </div>

                {{-- Page Title --}}
                <div class="d-none d-md-flex align-items-center">
                    <h5 class="mb-0 fw-semibold">@yield('page-title', 'Dashboard')</h5>
                </div>
            </div>

            <div class="d-flex align-items-center gap-1">
                {{-- Domain Selector --}}
                <div class="dropdown topbar-item">
                    <button type="button" class="topbar-button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center gap-1">
                            <iconify-icon icon="iconamoon:category-duotone" class="fs-22"></iconify-icon>
                            <span class="d-none d-md-inline fw-medium axiomeer-domain-label">Legal</span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">Select Domain</h6>
                        <a class="dropdown-item axiomeer-domain-item" href="#" data-domain="legal">
                            <i class="bx bx-briefcase text-primary fs-18 align-middle me-1"></i>
                            <span class="align-middle">Legal</span>
                        </a>
                        <a class="dropdown-item axiomeer-domain-item" href="#" data-domain="healthcare">
                            <i class="bx bx-plus-medical text-success fs-18 align-middle me-1"></i>
                            <span class="align-middle">Healthcare</span>
                        </a>
                        <a class="dropdown-item axiomeer-domain-item" href="#" data-domain="finance">
                            <i class="bx bx-bar-chart-alt-2 text-warning fs-18 align-middle me-1"></i>
                            <span class="align-middle">Finance</span>
                        </a>
                    </div>
                </div>

                {{-- Light/Dark Toggle --}}
                <div class="topbar-item">
                    <button type="button" class="topbar-button" id="light-dark-mode">
                        <iconify-icon icon="iconamoon:mode-dark-duotone" class="fs-24 align-middle"></iconify-icon>
                    </button>
                </div>

                {{-- Notifications --}}
                <div class="dropdown topbar-item">
                    <button type="button" class="topbar-button position-relative" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <iconify-icon icon="iconamoon:notification-duotone" class="fs-24 align-middle"></iconify-icon>
                    </button>
                    <div class="dropdown-menu py-0 dropdown-lg dropdown-menu-end">
                        <div class="p-3 border-top-0 border-start-0 border-end-0 border-dashed border">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0 fs-16 fw-semibold">Notifications</h6>
                                </div>
                                <div class="col-auto">
                                    <a href="javascript:void(0);" class="text-dark text-decoration-underline">
                                        <small>Clear All</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div style="max-height: 280px; overflow-y: auto;">
                            <div class="p-4 text-center text-muted">
                                <iconify-icon icon="iconamoon:notification-duotone" class="fs-36 mb-2 d-block"></iconify-icon>
                                <p class="mb-0">No notifications yet</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- User Menu --}}
                <div class="dropdown topbar-item">
                    <a type="button" class="topbar-button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        @if (auth()->user()->avatar)
                            <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                                 alt="{{ auth()->user()->name }}"
                                 class="rounded-circle"
                                 style="width: 32px; height: 32px; object-fit: cover;">
                        @else
                            <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle"
                                  style="width: 32px; height: 32px;">
                                <iconify-icon icon="iconamoon:profile-circle-duotone" class="text-primary" style="font-size: 22px;"></iconify-icon>
                            </span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">{{ auth()->user()->name ?? 'Welcome' }}</h6>
                        <span class="dropdown-item-text text-muted fs-12">{{ ucfirst(auth()->user()->role ?? 'viewer') }}</span>
                        <div class="dropdown-divider my-1"></div>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="bx bx-user-circle text-muted fs-18 align-middle me-1"></i>
                            <span class="align-middle">Profile</span>
                        </a>
                        <a class="dropdown-item" href="{{ route('settings') }}">
                            <i class="bx bx-cog text-muted fs-18 align-middle me-1"></i>
                            <span class="align-middle">Settings</span>
                        </a>
                        <a class="dropdown-item" href="{{ route('audit-log') }}">
                            <i class="bx bx-shield text-muted fs-18 align-middle me-1"></i>
                            <span class="align-middle">Audit Log</span>
                        </a>
                        <div class="dropdown-divider my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bx bx-log-out fs-18 align-middle me-1"></i>
                                <span class="align-middle">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'Axiomeer') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Space+Mono&display=swap" rel="stylesheet">

    {{-- Phosphor Icons --}}
    <script src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>

    {{-- Vite Assets --}}
    @vite(['resources/css/paper-ui.css', 'resources/css/app.css', 'resources/js/paper-ui.js', 'resources/js/app.js'])

    @stack('styles')
</head>
<body>
    <div class="app-shell">
        {{-- Sidebar --}}
        @include('partials.sidebar')

        {{-- Sidebar overlay (mobile) --}}
        <div class="sidebar-overlay"></div>

        {{-- Main area --}}
        <div class="main-area">
            {{-- Topbar --}}
            @include('partials.topbar')

            {{-- Page content --}}
            <div class="main-content">
                <div class="content-wrap">
                    @yield('content')
                </div>

                {{-- Footer --}}
                @include('partials.footer')
            </div>
        </div>
    </div>

    @stack('modals')
    @stack('scripts')
</body>
</html>

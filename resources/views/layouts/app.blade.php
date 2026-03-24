<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light" data-topbar-color="light" data-menu-color="light" data-menu-size="default">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'Axiomeer') }}</title>

    {{-- Favicon --}}
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">

    {{-- Theme Config (must load before CSS to set data attributes) --}}
    <script src="{{ asset('reback-css/config.js') }}"></script>

    {{-- Reback CSS --}}
    <link href="{{ asset('reback-css/vendor.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('reback-css/icons.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('reback-css/app.min.css') }}" rel="stylesheet" type="text/css">

    {{-- Axiomeer overrides --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body>
    {{-- Wrapper --}}
    <div class="wrapper">

        {{-- Topbar --}}
        @include('partials.topbar')

        {{-- Sidebar / App Menu --}}
        @include('partials.sidebar')

        {{-- Page Content --}}
        <div class="page-content">
            <div class="container-xxl">
                @yield('content')
            </div>

            {{-- Footer --}}
            @include('partials.footer')
        </div>

    </div>

    {{-- Reback JS --}}
    <script src="{{ asset('reback-css/vendor.min.js') }}"></script>
    <script src="{{ asset('reback-css/app.js') }}"></script>

    @stack('scripts')
</body>
</html>

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sign In') — {{ config('app.name', 'Axiomeer') }}</title>

    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">

    <link href="{{ asset('reback-css/vendor.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('reback-css/icons.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('reback-css/app.min.css') }}" rel="stylesheet" type="text/css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="text-center mb-4">
                <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                    <div class="axiomeer-logo-circle" style="width:52px;height:52px;min-width:52px;">
                        <img src="{{ asset('images/logo.png') }}" alt="Axiomeer" style="width:42px;height:42px;">
                    </div>
                    <span class="axiomeer-brand-text" style="font-size:22px;">Axiomeer</span>
                </a>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    @yield('content')
                </div>
            </div>

            <p class="text-center text-muted mt-3 fs-13">
                &copy; {{ date('Y') }} Axiomeer &mdash; Grounded Knowledge Assistant
            </p>
        </div>
    </div>
</body>
</html>

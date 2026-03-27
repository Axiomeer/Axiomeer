@extends('layouts.guest')
@section('title', 'Sign In')

@section('content')
<h4 class="fw-bold mb-1">Welcome back</h4>
<p class="text-muted mb-4">Sign in to your Axiomeer account</p>

@if (session('status'))
    <div class="alert alert-success fs-13">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror"
               id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror"
               id="password" name="password" required autocomplete="current-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
            <label class="form-check-label fs-13" for="remember_me">Remember me</label>
        </div>

        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="fs-13 text-primary">Forgot password?</a>
        @endif
    </div>

    <button type="submit" class="btn btn-primary w-100">Sign In</button>

    <div class="position-relative my-4">
        <hr class="text-muted opacity-25">
        <div class="position-absolute top-50 start-50 translate-middle px-2 bg-body text-muted fs-12 fw-medium">
            DEMO QUICK LOGIN
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 justify-content-center">
        <button type="button" class="btn btn-sm btn-outline-primary px-3 rounded-pill" onclick="demoLogin('admin@axiomeer.test')">Admin User</button>
        <button type="button" class="btn btn-sm btn-outline-success px-3 rounded-pill" onclick="demoLogin('analyst@axiomeer.test')">Analyst User</button>
        <button type="button" class="btn btn-sm btn-outline-info px-3 rounded-pill" onclick="demoLogin('viewer@axiomeer.test')">Viewer User</button>
    </div>

    <script>
        function demoLogin(email) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'password';
            document.querySelector('form').submit();
        }
    </script>
</form>
@endsection

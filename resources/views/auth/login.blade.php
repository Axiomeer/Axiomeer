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

    @if (Route::has('register'))
        <p class="text-center text-muted mt-3 mb-0 fs-13">
            Don't have an account? <a href="{{ route('register') }}" class="text-primary fw-medium">Create one</a>
        </p>
    @endif
</form>
@endsection

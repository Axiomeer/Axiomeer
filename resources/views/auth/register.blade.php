@extends('layouts.guest')
@section('title', 'Create Account')

@section('content')
<h4 class="fw-bold mb-1">Create Account</h4>
<p class="text-muted mb-4">Get started with Axiomeer</p>

<form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror"
               id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror"
               id="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror"
               id="password" name="password" required autocomplete="new-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input type="password" class="form-control"
               id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary w-100">Create Account</button>

    <p class="text-center text-muted mt-3 mb-0 fs-13">
        Already have an account? <a href="{{ route('login') }}" class="text-primary fw-medium">Sign in</a>
    </p>
</form>
@endsection

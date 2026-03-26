@extends('layouts.app')

@section('title', 'Profile')
@section('page-title', 'Profile')

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">My Profile</h4>
        <p class="text-muted mb-0 fs-13">Manage your account information and security settings</p>
    </div>
</div>

@if (session('status') === 'profile-updated')
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Profile information updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('status') === 'password-updated')
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Password updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('status') === 'avatar-updated')
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Profile photo updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('status') === 'avatar-removed')
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        Profile photo removed.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">

    {{-- Left Column: Avatar + Account Summary --}}
    <div class="col-xl-3 col-lg-4">
        <div class="card text-center">
            <div class="card-body py-4">

                {{-- Avatar with upload overlay --}}
                <div class="position-relative mx-auto mb-3" style="width: 96px; height: 96px;">
                    @if ($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}"
                             alt="{{ $user->name }}"
                             id="avatarPreview"
                             class="rounded-circle border border-2 border-primary-subtle"
                             style="width: 96px; height: 96px; object-fit: cover;">
                    @else
                        <div id="avatarPlaceholder" class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle"
                             style="width: 96px; height: 96px;">
                            <iconify-icon icon="iconamoon:profile-circle-duotone" class="text-primary" style="font-size: 56px;"></iconify-icon>
                        </div>
                        <img src="" alt="" id="avatarPreview"
                             class="rounded-circle border border-2 border-primary-subtle d-none"
                             style="width: 96px; height: 96px; object-fit: cover;">
                    @endif

                    {{-- Camera button overlay --}}
                    <label for="avatarFileInput"
                           class="position-absolute bottom-0 end-0 d-flex align-items-center justify-content-center rounded-circle bg-primary text-white"
                           style="width: 28px; height: 28px; cursor: pointer;" title="Change photo">
                        <iconify-icon icon="iconamoon:camera-duotone" style="font-size: 14px;"></iconify-icon>
                    </label>
                    <input type="file" id="avatarFileInput" class="d-none" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>

                {{-- Avatar action buttons — shown after picking a file --}}
                <div id="avatarActions" class="d-none mb-2">
                    <form id="avatarUploadForm" method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="avatar" id="avatarHiddenInput" class="d-none">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <iconify-icon icon="iconamoon:cloud-upload-duotone" class="me-1"></iconify-icon>Save Photo
                        </button>
                        <button type="button" id="avatarCancelBtn" class="btn btn-light btn-sm ms-1">Cancel</button>
                    </form>
                </div>

                {{-- Remove photo link (only if avatar set) --}}
                @if ($user->avatar)
                    <div class="mb-2">
                        <form method="POST" action="{{ route('profile.avatar.remove') }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-link btn-sm text-danger p-0 fs-12">Remove photo</button>
                        </form>
                    </div>
                @endif

                <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
                <p class="text-muted fs-13 mb-2">{{ $user->email }}</p>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fs-12">
                    {{ ucfirst($user->role ?? 'viewer') }}
                </span>
            </div>
            <div class="card-footer p-0">
                <div class="row g-0 border-top text-center">
                    <div class="col border-end py-3">
                        <p class="fw-semibold mb-0 fs-16">{{ \App\Models\Query::where('user_id', $user->id)->count() }}</p>
                        <p class="text-muted fs-12 mb-0">Queries</p>
                    </div>
                    <div class="col py-3">
                        <p class="fw-semibold mb-0 fs-16">{{ \App\Models\Document::where('uploaded_by', $user->id)->count() }}</p>
                        <p class="text-muted fs-12 mb-0">Documents</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Account Info Card --}}
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:shield-yes-duotone" class="text-success me-1"></iconify-icon>
                    Account Details
                </h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted fw-medium ps-3" style="width: 100px;">Role</td>
                        <td class="pe-3">{{ ucfirst($user->role ?? 'viewer') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium ps-3">Joined</td>
                        <td class="pe-3">{{ $user->created_at->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-medium ps-3">Email</td>
                        <td class="pe-3">
                            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && $user->hasVerifiedEmail())
                                <span class="badge bg-success-subtle text-success">Verified</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Unverified</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Right Column: Forms --}}
    <div class="col-xl-9 col-lg-8">

        {{-- Update Profile Info --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:edit-duotone" class="text-primary me-1"></iconify-icon>
                    Profile Information
                </h5>
            </div>
            <div class="card-body">
                <form id="send-verification" method="POST" action="{{ route('verification.send') }}">@csrf</form>

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                <div class="form-text text-warning">
                                    Email unverified.
                                    <button form="send-verification" class="btn btn-link btn-sm p-0 text-warning text-decoration-underline">
                                        Resend verification
                                    </button>
                                </div>
                            @endif
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <iconify-icon icon="iconamoon:cloud-upload-duotone" class="me-1"></iconify-icon>
                                Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Update Password --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:lock-duotone" class="text-warning me-1"></iconify-icon>
                    Change Password
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Current Password</label>
                            <input type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                                   name="current_password" autocomplete="current-password">
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">New Password</label>
                            <input type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                                   name="password" autocomplete="new-password">
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Confirm Password</label>
                            <input type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                                   name="password_confirmation" autocomplete="new-password">
                            @error('password_confirmation', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-warning">
                                <iconify-icon icon="iconamoon:lock-duotone" class="me-1"></iconify-icon>
                                Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Danger Zone --}}
        <div class="card border-danger">
            <div class="card-header border-danger">
                <h5 class="card-title mb-0 text-danger">
                    <iconify-icon icon="iconamoon:trash-duotone" class="me-1"></iconify-icon>
                    Danger Zone
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3 fs-14">Permanently delete your account and all associated data. This action cannot be undone.</p>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                    Delete My Account
                </button>
            </div>
        </div>

    </div>
</div>

{{-- Delete Account Modal --}}
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted fs-14">Confirm your password to permanently delete your account.</p>
                    <input type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                           name="password" placeholder="Your password" required>
                    @error('password', 'userDeletion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const fileInput    = document.getElementById('avatarFileInput');
    const hiddenInput  = document.getElementById('avatarHiddenInput');
    const preview      = document.getElementById('avatarPreview');
    const placeholder  = document.getElementById('avatarPlaceholder');
    const actions      = document.getElementById('avatarActions');
    const cancelBtn    = document.getElementById('avatarCancelBtn');

    if (!fileInput) return;

    let originalSrc = preview ? preview.src : '';

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Live preview
        const reader = new FileReader();
        reader.onload = (e) => {
            if (placeholder) placeholder.classList.add('d-none');
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);

        // Copy file to hidden input inside the upload form
        const dt = new DataTransfer();
        dt.items.add(file);
        hiddenInput.files = dt.files;

        actions.classList.remove('d-none');
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            fileInput.value = '';
            hiddenInput.value = '';
            actions.classList.add('d-none');

            // Restore original state
            if (originalSrc && originalSrc !== window.location.href) {
                preview.src = originalSrc;
                preview.classList.remove('d-none');
                if (placeholder) placeholder.classList.add('d-none');
            } else {
                preview.classList.add('d-none');
                if (placeholder) placeholder.classList.remove('d-none');
            }
        });
    }
})();
</script>
@endpush

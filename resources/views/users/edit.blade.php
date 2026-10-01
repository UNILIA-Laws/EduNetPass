@extends('layouts.app')
@section('title', 'eduroam | Edit ' . $user['uid'])

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <a href="{{ route('users.index') }}" class="small text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i>Back to users</a>

            <div class="mt-3">@include('users._alerts')</div>

            {{-- Details --}}
            <div class="card shadow-lg mb-4">
                <div class="card-header d-flex align-items-center">
                    <i class="fa-solid fa-user-pen me-3 fs-4"></i>
                    <h5 class="mb-0 fw-bold">Edit {{ $user['uid'] }}</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('users.update', $user['uid']) }}">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">First name</label>
                                <input name="givenName" value="{{ old('givenName', $user['givenName']) }}" class="form-control @error('givenName') is-invalid @enderror" required>
                                @error('givenName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Surname</label>
                                <input name="sn" value="{{ old('sn', $user['sn']) }}" class="form-control @error('sn') is-invalid @enderror" required>
                                @error('sn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Username (cannot be changed)</label>
                                <input value="{{ $user['uid'] }}" class="form-control" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Campus</label>
                                <select name="campus" class="form-select @error('campus') is-invalid @enderror">
                                    @foreach($campuses as $key => $c)
                                        <option value="{{ $key }}" @selected(old('campus', $campusKey) === $key)>{{ $c['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">E-mail</label>
                                <input type="email" name="mail" value="{{ old('mail', $user['mail']) }}" class="form-control @error('mail') is-invalid @enderror" required>
                                @error('mail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="d-grid mt-4"><button class="btn btn-eduroam"><i class="fa-solid fa-floppy-disk me-2"></i>Save changes</button></div>
                    </form>
                </div>
            </div>

            {{-- Reset password --}}
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1"><i class="fa-solid fa-key me-2 text-warning"></i>Reset password</h6>
                    <p class="small text-muted">The old password stops working immediately.</p>
                    <form method="POST" action="{{ route('users.reset', $user['uid']) }}"
                          onsubmit="return confirm('Reset the password for {{ $user['uid'] }}?')">
                        @csrf
                        <label class="form-label small fw-bold">New password <span class="text-muted fw-normal">(blank = auto-generate)</span></label>
                        <input type="text" name="userPassword" class="form-control mb-3 @error('userPassword') is-invalid @enderror" minlength="8" autocomplete="off">
                        @error('userPassword')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="send_email" value="1" id="sendEmail" checked>
                            <label class="form-check-label" for="sendEmail">E-mail the new password to {{ $user['mail'] ?: 'the student (no e-mail on file)' }}</label>
                        </div>
                        <button class="btn btn-outline-warning"><i class="fa-solid fa-key me-2"></i>Reset password</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
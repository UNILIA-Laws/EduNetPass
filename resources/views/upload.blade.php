@extends('layouts.app')
@section('title', 'eduroam | Add LDAP user')

@section('content')
@include('partials.prov-styles')

<div class="prov">
<div class="container">
    <div class="row g-4 g-xl-5">

        {{-- Left column: help --}}
        <div class="col-lg-5">
            <h1 class="prov-title">Add LDAP user</h1>
            <p class="prov-lead">Create one eduroam account and e-mail the login details to the user.</p>

            <ol class="flow">
                <li>
                    <span class="n">1</span>
                    <div>
                        <h3>Save the account to LDAP</h3>
                        <p>A new username is created. An existing username is updated.</p>
                    </div>
                </li>
                <li>
                    <span class="n">2</span>
                    <div>
                        <h3>E-mail the credentials</h3>
                        <p>Sent only after LDAP succeeds, so nobody receives a login that doesn't work.</p>
                    </div>
                </li>
                <li>
                    <span class="n">3</span>
                    <div>
                        <h3>Passwords are handled for you</h3>
                        <p>Leave the password blank and a secure one is generated.</p>
                    </div>
                </li>
            </ol>
        </div>

        {{-- Right column: form --}}
        <div class="col-lg-7">
            <div class="panel">

                <div class="panel-head">
                    <h2>User details</h2>
                    <a href="{{ route('enroll.bulk') }}" class="btn-ghost">Bulk upload instead</a>
                </div>

                @if($errors->has('ldap'))
                    <div class="notice">{{ $errors->first('ldap') }}</div>
                @endif

                <form action="{{ route('users.store') }}" method="POST" class="js-loading-form" autocomplete="off">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="givenName">First name</label>
                            <input id="givenName" name="givenName" value="{{ old('givenName') }}" class="form-control @error('givenName') is-invalid @enderror" required>
                            @error('givenName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sn">Surname</label>
                            <input id="sn" name="sn" value="{{ old('sn') }}" class="form-control @error('sn') is-invalid @enderror" required>
                            @error('sn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="uid">Username</label>
                            <input id="uid" name="uid" value="{{ old('uid') }}" placeholder="beh-01-001-24" class="form-control @error('uid') is-invalid @enderror" required>
                            @error('uid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="Reg">Registration number <span class="hint">(optional)</span></label>
                            <input id="Reg" name="Reg" value="{{ old('Reg') }}" placeholder="BEH/01/001/24" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="mail">E-mail <span class="hint">(credentials are sent here)</span></label>
                            <input id="mail" type="email" name="mail" value="{{ old('mail') }}" class="form-control @error('mail') is-invalid @enderror" required>
                            @error('mail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pwd">Password <span class="hint">(blank = generate)</span></label>
                            <div class="input-group">
                                <input type="text" name="userPassword" id="pwd" class="form-control @error('userPassword') is-invalid @enderror" minlength="8">
                                <button type="button" class="btn" id="genPwd" title="Generate a password">Generate</button>
                                @error('userPassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="campus">Campus</label>
                            <select name="campus" id="campus" class="form-select">
                                @foreach($campuses as $key => $c)
                                    <option value="{{ $key }}" @selected(old('campus') === $key)>{{ $c['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-eduroam">
                            <span class="btn-text">Create account and send credentials</span>
                            <span class="btn-loading d-none"><span class="spinner-border spinner-border-sm me-2"></span>Working...</span>
                        </button>
                    </div>
                </form>

                @include('partials.prov-results')

            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    // Disable button + spinner while the server works
    document.querySelectorAll('.js-loading-form').forEach(f => f.addEventListener('submit', () => {
        const b = f.querySelector('button[type=submit]');
        b.disabled = true;
        b.querySelector('.btn-text').classList.add('d-none');
        b.querySelector('.btn-loading').classList.remove('d-none');
    }));

    // Random password generator
    document.getElementById('genPwd').addEventListener('click', () => {
        const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        const rnd = crypto.getRandomValues(new Uint32Array(10));
        document.getElementById('pwd').value = Array.from(rnd, n => chars[n % chars.length]).join('');
    });
</script>
@endpush
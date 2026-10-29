@extends('layouts.app')
@section('title', 'eduroam | Add LDAP user')

@section('content')
@include('partials.prov-styles')

@php
    // Works even if the controller doesn't pass $types
    $types       = $types ?? config('ldap.user_types');
    $currentType = old('type', array_key_first($types));
@endphp

<div class="prov">
<div class="container">
    <div class="row g-4 g-xl-5">

        {{-- Left column: help --}}
        <div class="col-lg-5">
            <h1 class="prov-title">Add LDAP user</h1>
            <p class="prov-lead">Create one eduroam account (student, staff, lecturer or guest) and e-mail the login details to the user.</p>

            <ol class="flow">
                <li>
                    <span class="n">1</span>
                    <div>
                        <h3>Choose the user type</h3>
                        <p>The type decides the account's eduroam affiliation. An existing username of a different type is never overwritten.</p>
                    </div>
                </li>
                <li>
                    <span class="n">2</span>
                    <div>
                        <h3>Save the account to LDAP</h3>
                        <p>A new username is created. An existing username of the same type is updated.</p>
                    </div>
                </li>
                <li>
                    <span class="n">3</span>
                    <div>
                        <h3>E-mail the credentials</h3>
                        <p>Sent only after LDAP succeeds, so nobody receives a login that doesn't work. Leave the password blank and a secure one is generated.</p>
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
                            <label class="form-label" for="type">User type</label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                @foreach($types as $key => $t)
                                    <option value="{{ $key }}"
                                            data-reg="{{ ($t['uses_reg'] ?? $key === 'student') ? '1' : '0' }}"
                                            data-hint="{{ $t['uid_hint'] ?? '' }}"
                                            @selected($currentType === $key)>{{ $t['label'] }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="campus">Campus</label>
                            <select name="campus" id="campus" class="form-select @error('campus') is-invalid @enderror" required>
                                @foreach($campuses as $key => $c)
                                    <option value="{{ $key }}" @selected(old('campus') === $key)>{{ $c['label'] }}</option>
                                @endforeach
                            </select>
                            @error('campus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

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

                        <div class="col-md-6" id="uidCol">
                            <label class="form-label" for="uid">Username</label>
                            <input id="uid" name="uid" value="{{ old('uid') }}" placeholder="beh-01-001-24" class="form-control @error('uid') is-invalid @enderror" required>
                            @error('uid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Only shown for types that use a registration number (students by default) --}}
                        <div class="col-md-6" id="regCol">
                            <label class="form-label" for="Reg">Registration number <span class="hint">(optional, shown in the e-mail)</span></label>
                            <input id="Reg" name="Reg" value="{{ old('Reg') }}" placeholder="BEH/01/001/24" class="form-control @error('Reg') is-invalid @enderror">
                            @error('Reg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="mail">E-mail <span class="hint">(credentials are sent here)</span></label>
                            <input id="mail" type="email" name="mail" value="{{ old('mail') }}" class="form-control @error('mail') is-invalid @enderror" required>
                            @error('mail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="pwd">Password <span class="hint">(blank = generate)</span></label>
                            <div class="input-group">
                                <input type="text" name="userPassword" id="pwd" class="form-control @error('userPassword') is-invalid @enderror" minlength="8">
                                <button type="button" class="btn" id="genPwd" title="Generate a password">Generate</button>
                                @error('userPassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-eduroam">
                            <span class="btn-text">Create account</span> and send credentials</span>
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

    // Adapt the form to the chosen user type
    const typeSel = document.getElementById('type');
    const regCol  = document.getElementById('regCol');
    const regInp  = document.getElementById('Reg');
    const uidCol  = document.getElementById('uidCol');
    const uidInp  = document.getElementById('uid');
    const typeWord = document.getElementById('typeWord');
    const defaultUidPlaceholder = uidInp.placeholder;

    function applyType() {
        const opt = typeSel.options[typeSel.selectedIndex];
        const usesReg = opt.dataset.reg === '1';

        regCol.classList.toggle('d-none', ! usesReg);
        if (! usesReg) regInp.value = '';           // never send a stale value

        // Username takes the full row when there is no Reg field next to it
        uidCol.classList.toggle('col-md-6', usesReg);
        uidCol.classList.toggle('col-12', ! usesReg);

        uidInp.placeholder = opt.dataset.hint || defaultUidPlaceholder;
        typeWord.textContent = opt.textContent.trim().toLowerCase() + ' account';
    }

    typeSel.addEventListener('change', applyType);
    applyType();
</script>
@endpush
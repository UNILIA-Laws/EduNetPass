@extends('layouts.app')
@section('title', 'eduroam | Edit ' . $user['uid'])

@section('content')
@include('partials.usr-style')

@php
    $types        = $types ?? config('ldap.user_types');
    $initials     = mb_strtoupper(mb_substr($user['givenName'] ?? '', 0, 1) . mb_substr($user['sn'] ?? '', 0, 1)) ?: '?';
    $fullName     = trim(($user['givenName'] ?? '') . ' ' . ($user['sn'] ?? '')) ?: $user['uid'];
    $currentType  = $user['type'];                       // null when the account matches no configured type
    $selectedType = old('type', $currentType ?? '');
    $selectedCampus = old('campus', $campusKey);
@endphp

<div class="usr">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            @include('users._alerts')

            <div class="usr-head">
                <div class="person">
                    <span class="avatar" aria-hidden="true" style="width:48px;height:48px;font-size:1rem">{{ $initials }}</span>
                    <div>
                        <h1 class="usr-title mb-0">{{ $fullName }}</h1>
                        <div class="id">{{ $user['uid'] }}
                            <span class="chip {{ $currentType ? 'type-' . $currentType : '' }} ms-1">
                                {{ $currentType ? ($types[$currentType]['label'] ?? ucfirst($currentType)) : 'Other' }}
                            </span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('users.index') }}" class="btn-clear"><i class="fa-solid fa-arrow-left me-2"></i>Back to users</a>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <span class="ico"><i class="fa-solid fa-pen"></i></span>Edit details
                </div>

                <div class="panel-body">
                    <p class="usr-sub mb-3">Update this user's name, e-mail, campus or type. The username can't be changed.</p>

                    <form method="POST" action="{{ route('users.update', $user['uid']) }}" id="editForm">
                        @csrf @method('PUT')
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label" for="givenName">First name</label>
                                <input id="givenName" name="givenName" value="{{ old('givenName', $user['givenName']) }}" class="form-control @error('givenName') is-invalid @enderror" required>
                                @error('givenName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sn">Surname</label>
                                <input id="sn" name="sn" value="{{ old('sn', $user['sn']) }}" class="form-control @error('sn') is-invalid @enderror" required>
                                @error('sn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="uid">Username <span class="hint">(locked)</span></label>
                                <input id="uid" value="{{ $user['uid'] }}" class="form-control" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="type">User type</label>
                                <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" data-current="{{ $currentType }}">
                                    @if(! $currentType)
                                        <option value="" @selected($selectedType === '')>Keep current (not a configured type)</option>
                                    @endif
                                    @foreach($types as $key => $t)
                                        <option value="{{ $key }}" @selected($selectedType === $key)>{{ $t['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="mail">E-mail</label>
                                <input id="mail" type="email" name="mail" value="{{ old('mail', $user['mail']) }}" class="form-control @error('mail') is-invalid @enderror" required>
                                @error('mail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="campus">Campus</label>
                                <select id="campus" name="campus" class="form-select @error('campus') is-invalid @enderror" required>
                                    @if(! $selectedCampus)
                                        <option value="" selected disabled>Select a campus…</option>
                                    @endif
                                    @foreach($campuses as $key => $c)
                                        <option value="{{ $key }}" @selected($selectedCampus === $key)>{{ $c['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('campus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="note warn d-none mt-3 mb-0" id="typeNote">
                            <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                            <span>Changing the type replaces this user's eduroam affiliation. They keep the same username and password.</span>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('users.index') }}" class="btn-clear">Cancel</a>
                            <button class="btn-primary-soft px-4"><i class="fa-solid fa-floppy-disk me-2"></i>Save changes</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    // Warn when the type is being changed
    const typeSel  = document.getElementById('type');
    const typeNote = document.getElementById('typeNote');

    function toggleTypeNote() {
        const changed = typeSel.value !== '' && typeSel.value !== typeSel.dataset.current;
        typeNote.classList.toggle('d-none', ! changed);
    }
    typeSel.addEventListener('change', toggleTypeNote);
    toggleTypeNote();
</script>
@endpush
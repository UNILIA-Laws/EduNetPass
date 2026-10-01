@extends('layouts.app')
@section('title', 'eduroam | Edit ' . $user['uid'])

@section('content')
@php
    $initials = mb_strtoupper(mb_substr($user['givenName'] ?? '', 0, 1) . mb_substr($user['sn'] ?? '', 0, 1)) ?: '?';
@endphp

<style>
    .edt {
        --ink: #172033;
        --muted: #667085;
        --line: #E3E8F0;
        --surface: #FFFFFF;
        --canvas: #F3F5F9;
        --accent: #2557D6;
        --accent-ink: #1B44AB;
        --accent-soft: #E8EEFC;
        --warn: #A8620B;  --warn-soft: #FDF1DF;
        --idle-soft: #EEF1F5;
        background: var(--canvas);
        color: var(--ink);
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        padding: 2rem 0 3rem;
    }
    .edt :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

    .back {
        display: inline-flex; align-items: center; gap: .4rem; font-size: .85rem; font-weight: 600;
        color: var(--muted); text-decoration: none; margin-bottom: 1rem;
    }
    .back:hover { color: var(--accent-ink); }

    /* Identity header */
    .who { display: flex; align-items: center; gap: .9rem; margin-bottom: 1.25rem; }
    .who .avatar {
        flex: none; width: 52px; height: 52px; border-radius: 50%; display: grid; place-items: center;
        background: var(--accent-soft); color: var(--accent-ink); font-size: 1.05rem; font-weight: 700;
    }
    .who h1 { font-size: 1.4rem; font-weight: 700; letter-spacing: -.02em; margin: 0; line-height: 1.2; }
    .who .id { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .82rem; color: var(--muted); }

    /* Panels */
    .panel {
        background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 1.25rem;
        box-shadow: 0 1px 2px rgba(23,32,51,.04), 0 8px 24px -12px rgba(23,32,51,.12);
        margin-bottom: 1.25rem;
    }
    @media (min-width: 992px) { .panel { padding: 1.5rem; } }
    .panel h2 { font-size: 1rem; font-weight: 700; margin: 0 0 .2rem; letter-spacing: -.01em; }
    .panel .desc { color: var(--muted); font-size: .84rem; margin: 0 0 1.1rem; }

    .panel.danger-zone { box-shadow: none; }
    .panel.danger-zone h2 { display: flex; align-items: center; gap: .55rem; }
    .panel.danger-zone .ico {
        width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center;
        background: var(--warn-soft); color: var(--warn); font-size: .8rem;
    }

    /* Fields */
    .edt .form-label { font-size: .8rem; font-weight: 600; color: var(--ink); margin-bottom: .3rem; }
    .edt .form-label .hint { color: var(--muted); font-weight: 400; }
    .edt .form-control, .edt .form-select {
        font-size: .88rem; border: 1px solid #D5DBE6; border-radius: 8px; padding: .5rem .75rem; background-color: #fff; color: var(--ink);
        transition: border-color .15s, box-shadow .15s;
    }
    .edt .form-control:focus, .edt .form-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,87,214,.15); }
    .edt .form-control:disabled { background: #F7F9FC; color: var(--muted); border-style: dashed; }
    .edt .form-check-input:checked { background-color: var(--accent); border-color: var(--accent); }
    .edt .form-check-label { font-size: .86rem; color: #475467; }

    /* Buttons */
    .edt .btn-eduroam {
        background: var(--accent); color: #fff; border: 0; border-radius: 10px;
        font-size: .9rem; font-weight: 600; padding: .6rem 1.25rem; box-shadow: none; transition: background .15s;
    }
    .edt .btn-eduroam:hover { background: var(--accent-ink); color: #fff; }
    .btn-warn {
        background: var(--warn-soft); color: var(--warn); border: 0; border-radius: 10px;
        font-size: .9rem; font-weight: 600; padding: .6rem 1.25rem; transition: background .15s;
    }
    .btn-warn:hover { background: #FAE5C4; color: var(--warn); }
    .actions { display: flex; justify-content: flex-end; margin-top: 1.25rem; }
</style>

<div class="edt">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <a href="{{ route('users.index') }}" class="back"><i class="fa-solid fa-arrow-left"></i>Back to users</a>

            <div class="who">
                <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                <div>
                    <h1>{{ trim(($user['givenName'] ?? '') . ' ' . ($user['sn'] ?? '')) ?: $user['uid'] }}</h1>
                    <div class="id">{{ $user['uid'] }}</div>
                </div>
            </div>

            @include('users._alerts')

            {{-- Details --}}
            <div class="panel">
                <h2>Details</h2>
                <p class="desc">Update the student's name, e-mail or campus. The username can't be changed.</p>

                <form method="POST" action="{{ route('users.update', $user['uid']) }}">
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
                            <label class="form-label" for="campus">Campus</label>
                            <select id="campus" name="campus" class="form-select @error('campus') is-invalid @enderror">
                                @foreach($campuses as $key => $c)
                                    <option value="{{ $key }}" @selected(old('campus', $campusKey) === $key)>{{ $c['label'] }}</option>
                                @endforeach
                            </select>
                            @error('campus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="mail">E-mail</label>
                            <input id="mail" type="email" name="mail" value="{{ old('mail', $user['mail']) }}" class="form-control @error('mail') is-invalid @enderror" required>
                            @error('mail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn btn-eduroam"><i class="fa-solid fa-floppy-disk me-2"></i>Save changes</button>
                    </div>
                </form>
            </div>

            {{-- Reset password --}}
            <div class="panel danger-zone">
                <h2><span class="ico"><i class="fa-solid fa-key"></i></span>Reset password</h2>
                <p class="desc">The old password stops working immediately.</p>

                <form method="POST" action="{{ route('users.reset', $user['uid']) }}"
                      onsubmit="return confirm('Reset the password for {{ $user['uid'] }}?')">
                    @csrf
                    <label class="form-label" for="userPassword">New password <span class="hint">(blank = generate)</span></label>
                    <input id="userPassword" type="text" name="userPassword" class="form-control mb-3 @error('userPassword') is-invalid @enderror" minlength="8" autocomplete="off">
                    @error('userPassword')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="send_email" value="1" id="sendEmail" checked>
                        <label class="form-check-label" for="sendEmail">E-mail the new password to {{ $user['mail'] ?: 'the student (no e-mail on file)' }}</label>
                    </div>
                    <button class="btn-warn"><i class="fa-solid fa-key me-2"></i>Reset password</button>
                </form>
            </div>

        </div>
    </div>
</div>
</div>
@endsection
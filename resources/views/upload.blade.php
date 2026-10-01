@extends('layouts.app')
@section('title', 'eduroam | Student Provisioning')

@section('content')
@php
    $tab = session('tab', 'bulk');
    $results = collect(session('results', []));
    $badge = fn($v) => match($v) {
        'created', 'sent' => 'ok', 'updated' => 'info', 'failed' => 'bad', default => 'idle',
    };
    $ldapCount  = $results->whereIn('ldap', ['created', 'updated'])->count();
    $mailCount  = $results->where('email', 'sent')->count();
    $errorCount = $results->filter(fn($r) => $r['error'])->count();
@endphp

<style>
    .prov {
        --ink: #172033;
        --muted: #667085;
        --line: #E3E8F0;
        --surface: #FFFFFF;
        --canvas: #F3F5F9;
        --accent: #2557D6;
        --accent-ink: #1B44AB;
        --accent-soft: #E8EEFC;
        --ok: #178A5C;      --ok-soft: #E3F5EC;
        --bad: #C93A3A;     --bad-soft: #FCE9E9;
        --info: #2557D6;    --info-soft: #E8EEFC;
        --idle: #667085;    --idle-soft: #EEF1F5;
        background: var(--canvas);
        color: var(--ink);
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        min-height: 100%;
        padding: 2rem 0 3rem;
    }
    .prov * { box-sizing: border-box; }

    /* Page heading */
    .prov-title { font-size: 1.6rem; font-weight: 700; letter-spacing: -.02em; margin: 0 0 .35rem; }
    .prov-lead  { color: var(--muted); font-size: .95rem; max-width: 42ch; margin: 0 0 1.75rem; line-height: 1.55; }

    /* Process: this really is a sequence, so it is numbered and connected */
    .flow { list-style: none; margin: 0 0 1.75rem; padding: 0; position: relative; }
    .flow li { position: relative; display: flex; gap: .85rem; padding-bottom: 1.15rem; }
    .flow li:last-child { padding-bottom: 0; }
    .flow li:not(:last-child)::before {
        content: ""; position: absolute; left: 13px; top: 28px; bottom: 2px; width: 2px; background: var(--line);
    }
    .flow .n {
        flex: none; width: 28px; height: 28px; border-radius: 50%;
        display: grid; place-items: center; font-size: .78rem; font-weight: 700;
        background: var(--accent); color: #fff;
    }
    .flow .n.done { background: var(--ok); }
    .flow h3 { font-size: .92rem; font-weight: 650; margin: 3px 0 .15rem; }
    .flow p  { font-size: .84rem; color: var(--muted); margin: 0; line-height: 1.5; }

    /* File format panel */
    .format {
        background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 1rem 1.1rem;
    }
    .format-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .8rem; }
    .format-head h3 { font-size: .92rem; font-weight: 650; margin: 0; }
    .format-head span { font-size: .78rem; color: var(--muted); }
    .cols { display: flex; flex-wrap: wrap; gap: .4rem; margin: 0; padding: 0; list-style: none; }
    .cols li {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .78rem;
        padding: .25rem .55rem; border-radius: 6px;
        background: var(--accent-soft); color: var(--accent-ink); border: 1px solid transparent;
    }
    .cols li.optional { background: transparent; color: var(--muted); border: 1px dashed #C5CDDA; }
    .format-note { font-size: .78rem; color: var(--muted); margin: .75rem 0 0; line-height: 1.5; }
    .format-note .swatch {
        display: inline-block; width: 22px; height: 10px; border: 1px dashed #C5CDDA; border-radius: 3px; vertical-align: -1px; margin-right: 2px;
    }
    .btn-ghost {
        font-size: .8rem; font-weight: 600; color: var(--accent-ink); background: var(--accent-soft);
        border: 0; border-radius: 999px; padding: .35rem .85rem; text-decoration: none; white-space: nowrap;
        transition: background .15s;
    }
    .btn-ghost:hover { background: #D8E3FA; color: var(--accent-ink); }

    /* Main card */
    .panel {
        background: var(--surface); border: 1px solid var(--line); border-radius: 16px;
        box-shadow: 0 1px 2px rgba(23,32,51,.04), 0 8px 24px -12px rgba(23,32,51,.12);
        padding: 1.25rem;
    }
    @media (min-width: 992px) { .panel { padding: 1.5rem; } }
    .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.1rem; flex-wrap: wrap; }
    .panel-head h2 { font-size: 1.05rem; font-weight: 700; margin: 0; letter-spacing: -.01em; }

    /* Segmented tabs */
    .seg { display: inline-flex; background: var(--idle-soft); border-radius: 10px; padding: 3px; gap: 2px; }
    .seg .nav-link {
        border: 0; background: transparent; color: var(--muted); font-size: .84rem; font-weight: 600;
        padding: .35rem .85rem; border-radius: 8px; transition: background .15s, color .15s;
    }
    .seg .nav-link:hover { color: var(--ink); }
    .seg .nav-link.active { background: var(--surface); color: var(--ink); box-shadow: 0 1px 2px rgba(23,32,51,.12); }
    .prov :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

    /* Fields */
    .prov .form-label { font-size: .8rem; font-weight: 600; color: var(--ink); margin-bottom: .3rem; }
    .prov .form-label .hint { color: var(--muted); font-weight: 400; }
    .prov .form-control, .prov .form-select {
        font-size: .88rem; border: 1px solid #D5DBE6; border-radius: 8px; padding: .48rem .7rem; background-color: #fff; color: var(--ink);
        transition: border-color .15s, box-shadow .15s;
    }
    .prov .form-control::placeholder { color: #A5AEBD; }
    .prov .form-control:focus, .prov .form-select:focus {
        border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,87,214,.15);
    }
    .prov .input-group .btn {
        border: 1px solid #D5DBE6; border-left: 0; color: var(--muted); background: #F7F9FC; border-radius: 0 8px 8px 0;
    }
    .prov .input-group .btn:hover { color: var(--accent); background: var(--accent-soft); }
    .prov .input-group .form-control { border-radius: 8px 0 0 8px; }

    /* Upload zone */
    .drop {
        display: flex; flex-direction: column; align-items: center; gap: .15rem; text-align: center; cursor: pointer;
        border: 1.5px dashed #BFC9DA; border-radius: 12px; background: #FAFBFD; padding: 1.5rem 1rem;
        transition: border-color .15s, background .15s;
    }
    .drop:hover, .drop.drag { border-color: var(--accent); background: var(--accent-soft); }
    .drop .icon {
        width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; margin-bottom: .5rem;
        background: var(--surface); color: var(--accent); border: 1px solid var(--line); font-size: 1.15rem;
    }
    .drop strong { font-size: .95rem; }
    .drop small { color: var(--muted); font-size: .8rem; }
    #file-name:empty { display: none; }
    #file-name {
        margin-top: .6rem; font-size: .82rem; font-weight: 600; color: var(--ok); background: var(--ok-soft);
        padding: .25rem .7rem; border-radius: 999px;
    }

    /* Primary button */
    .prov .btn-eduroam {
        background: var(--accent); color: #fff; border: 0; border-radius: 10px;
        font-size: .9rem; font-weight: 600; padding: .65rem 1rem; box-shadow: none;
        transition: background .15s;
    }
    .prov .btn-eduroam:hover { background: var(--accent-ink); color: #fff; }
    .prov .btn-eduroam:disabled { background: #7C97DE; opacity: 1; }

    /* Alerts */
    .notice {
        display: flex; gap: .6rem; align-items: flex-start; background: var(--bad-soft); color: #8E2323;
        border-radius: 10px; padding: .65rem .85rem; font-size: .86rem; margin-bottom: 1rem;
    }

    /* Results */
    .results { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--line); }
    .results h3 { font-size: .95rem; font-weight: 650; margin: 0 0 .75rem; }
    .tally { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .9rem; }
    .tally div {
        display: flex; align-items: baseline; gap: .4rem; padding: .4rem .75rem; border-radius: 10px; font-size: .82rem;
    }
    .tally b { font-size: 1.15rem; font-weight: 700; }
    .tally .ok  { background: var(--ok-soft);  color: var(--ok); }
    .tally .bad { background: var(--bad-soft); color: var(--bad); }
    .tally .none { background: var(--idle-soft); color: var(--idle); }

    .tbl-wrap { max-height: 360px; overflow: auto; border: 1px solid var(--line); border-radius: 12px; }
    .tbl { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .82rem; }
    .tbl th {
        position: sticky; top: 0; background: #F7F9FC; text-align: left; font-weight: 600; color: var(--muted);
        padding: .55rem .8rem; border-bottom: 1px solid var(--line); white-space: nowrap;
    }
    .tbl td { padding: .6rem .8rem; border-bottom: 1px solid #EEF1F6; vertical-align: top; }
    .tbl tr:last-child td { border-bottom: 0; }
    .tbl .sub { color: var(--muted); font-size: .76rem; }
    .tbl .num { color: var(--muted); }
    .tbl code { background: var(--idle-soft); color: var(--ink); border-radius: 5px; padding: .1rem .35rem; }
    .tbl .err { color: var(--bad); }

    .pill {
        display: inline-flex; align-items: center; gap: .35rem; padding: .15rem .6rem; border-radius: 999px;
        font-size: .76rem; font-weight: 600;
    }
    .pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .pill.ok   { background: var(--ok-soft);   color: var(--ok); }
    .pill.info { background: var(--info-soft); color: var(--info); }
    .pill.bad  { background: var(--bad-soft);  color: var(--bad); }
    .pill.idle { background: var(--idle-soft); color: var(--idle); }
</style>

<div class="prov">
<div class="container">
    <div class="row g-4 g-xl-5">

        {{-- Left column: help --}}
        <div class="col-lg-5">
            <h1 class="prov-title">Student enrollment</h1>
            <p class="prov-lead">Create eduroam accounts and send each student their login details in one step.</p>

            <ol class="flow">
                <li>
                    <span class="n">1</span>
                    <div>
                        <h3>Save the account to LDAP</h3>
                        <p>New usernames are created. Existing usernames are updated.</p>
                    </div>
                </li>
                <li>
                    <span class="n">2</span>
                    <div>
                        <h3>E-mail the credentials</h3>
                        <p>Sent only after LDAP succeeds, so no student receives a login that doesn't work.</p>
                    </div>
                </li>
                <li>
                    <span class="n done"><i class="fa-solid fa-check" style="font-size:.7rem"></i></span>
                    <div>
                        <h3>Passwords are handled for you</h3>
                        <p>Leave the password blank and a secure one is generated.</p>
                    </div>
                </li>
            </ol>

            <div class="format">
                <div class="format-head">
                    <div>
                        <h3>Bulk file format</h3>
                        <span>CSV or Excel (.xlsx)</span>
                    </div>
                    <a href="{{ route('download.sample') }}" class="btn-ghost"><i class="fa-solid fa-download me-1"></i> Sample file</a>
                </div>
                <ul class="cols">
                    <li>givenName</li>
                    <li>sn</li>
                    <li class="optional">Reg</li>
                    <li>mail</li>
                    <li>uid</li>
                    <li class="optional">userPassword</li>
                </ul>
                <p class="format-note">
                    Put these headers in the first row. Columns with a <span class="swatch"></span> dashed outline are optional.
                </p>
            </div>
        </div>

        {{-- Right column: forms --}}
        <div class="col-lg-7">
            <div class="panel">

                <div class="panel-head">
                    <h2>Add students</h2>
                    <ul class="nav seg" role="tablist">
                        <li class="nav-item"><button class="nav-link {{ $tab === 'bulk' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#tab-bulk" type="button"><i class="fa-solid fa-file-import me-1"></i> Bulk upload</button></li>
                        <li class="nav-item"><button class="nav-link {{ $tab === 'single' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#tab-single" type="button"><i class="fa-solid fa-user-plus me-1"></i> Single student</button></li>
                    </ul>
                </div>

                @if($errors->has('ldap'))
                    <div class="notice"><i class="fa-solid fa-triangle-exclamation mt-1"></i><span>{{ $errors->first('ldap') }}</span></div>
                @endif

                <div class="tab-content">

                    {{-- BULK --}}
                    <div class="tab-pane fade {{ $tab === 'bulk' ? 'show active' : '' }}" id="tab-bulk">
                        <form action="{{ route('upload.csv') }}" method="POST" enctype="multipart/form-data" class="js-loading-form">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="bulk-campus">Campus</label>
                                <select name="campus" id="bulk-campus" class="form-select">
                                    @foreach($campuses as $key => $c)
                                        <option value="{{ $key }}" @selected(old('campus') === $key)>{{ $c['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="drop" for="csv_file" id="dropzone">
                                    <span class="icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <strong>Choose a CSV or Excel file</strong>
                                    <small>or drag it here</small>
                                    <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt,.xlsx" required hidden>
                                    <span id="file-name"></span>
                                </label>
                                @error('csv_file')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-eduroam">
                                    <span class="btn-text"><i class="fa-solid fa-paper-plane me-2"></i>Create accounts and send credentials</span>
                                    <span class="btn-loading d-none"><span class="spinner-border spinner-border-sm me-2"></span>Working. Keep this page open.</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- SINGLE --}}
                    <div class="tab-pane fade {{ $tab === 'single' ? 'show active' : '' }}" id="tab-single">
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
                                        <button type="button" class="btn" id="genPwd" title="Generate a password" aria-label="Generate a password"><i class="fa-solid fa-wand-magic-sparkles"></i></button>
                                        @error('userPassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="single-campus">Campus</label>
                                    <select name="campus" id="single-campus" class="form-select">
                                        @foreach($campuses as $key => $c)
                                            <option value="{{ $key }}" @selected(old('campus') === $key)>{{ $c['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-eduroam">
                                    <span class="btn-text"><i class="fa-solid fa-user-check me-2"></i>Create account and send credentials</span>
                                    <span class="btn-loading d-none"><span class="spinner-border spinner-border-sm me-2"></span>Working...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- RESULTS --}}
                @if($results->isNotEmpty())
                    <div class="results">
                        <h3>Results</h3>
                        <div class="tally">
                            <div class="ok"><b>{{ $ldapCount }}</b> saved to LDAP</div>
                            <div class="ok"><b>{{ $mailCount }}</b> e-mailed</div>
                            <div class="{{ $errorCount ? 'bad' : 'none' }}"><b>{{ $errorCount }}</b> {{ $errorCount === 1 ? 'problem' : 'problems' }}</div>
                        </div>

                        <div class="tbl-wrap">
                            <table class="tbl">
                                <thead><tr><th>#</th><th>Username</th><th>Student</th><th>LDAP</th><th>E-mail</th><th>Notes</th></tr></thead>
                                <tbody>
                                @foreach($results as $r)
                                    <tr>
                                        <td class="num">{{ $r['line'] }}</td>
                                        <td>{{ $r['uid'] }}</td>
                                        <td>{{ $r['name'] }}<div class="sub">{{ $r['mail'] }}</div></td>
                                        <td><span class="pill {{ $badge($r['ldap']) }}">{{ $r['ldap'] }}</span></td>
                                        <td><span class="pill {{ $badge($r['email']) }}">{{ $r['email'] }}</span></td>
                                        <td>
                                            @if($r['error'])<div class="err">{{ $r['error'] }}</div>@endif
                                            @if(!empty($r['password']))
                                                <div>Give the student this password manually: <code>{{ $r['password'] }}</code></div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')

<script>
    const input = document.getElementById('csv_file');
    const nameBox = document.getElementById('file-name');
    const zone = document.getElementById('dropzone');

    function showName() {
        nameBox.textContent = input.files.length ? input.files[0].name : '';
    }
    input.addEventListener('change', showName);
    ['dragover', 'dragenter'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.add('drag'); }));
    ['dragleave', 'drop'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.remove('drag'); }));
    zone.addEventListener('drop', ev => { input.files = ev.dataTransfer.files; showName(); });

    // Disable button + spinner while the server works
    document.querySelectorAll('.js-loading-form').forEach(f => f.addEventListener('submit', () => {
        const b = f.querySelector('button[type=submit]');
        b.disabled = true;
        b.querySelector('.btn-text').classList.add('d-none');
        b.querySelector('.btn-loading').classList.remove('d-none');
    }));

    // Random password generator for the single-student form
    document.getElementById('genPwd').addEventListener('click', () => {
        const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        const rnd = crypto.getRandomValues(new Uint32Array(10));
        document.getElementById('pwd').value = Array.from(rnd, n => chars[n % chars.length]).join('');
    });
</script>
@endpush
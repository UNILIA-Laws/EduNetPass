@extends('layouts.app')
@section('title', 'eduroam | Bulk upload')

@section('content')
@include('partials.usr-style')

@php
    $uploadRoute = 'upload.csv';        // POST -> ProvisioningController@upload
    $sampleRoute = 'download.sample';   // GET  -> ProvisioningController@downloadSample

    $types    = $types ?? config('ldap.user_types');
    $results  = collect(session('results', []));
    $maxRows  = (int) config('ldap.max_rows_per_upload');

    $count = fn (string $key, string $val) => $results->where($key, $val)->count();
    $created = $count('ldap', 'created');
    $updated = $count('ldap', 'updated');
    $failed  = $results->filter(fn ($r) => $r['ldap'] === 'failed' || ($r['ldap'] === 'skipped' && ! empty($r['error'])))->count();
    $mailed  = $count('email', 'sent');
    $mailBad = $count('email', 'failed');
@endphp

<style>
    /* status chips for the results table (built on the shared .chip) */
    .usr .chip.ok   { background: var(--good-soft); color: var(--good); }
    .usr .chip.fail { background: var(--bad-soft);  color: var(--bad); }
    .usr .chip.skip { background: var(--idle-soft); color: #475467; }
    .usr .stat { display: flex; flex-wrap: wrap; gap: .5rem; }
    .usr .stat .chip { font-size: .82rem; padding: .3rem .8rem; }
    .usr .cols td, .usr .cols th { padding: .45rem .75rem; font-size: .82rem; }
    .usr .cols code { background: var(--idle-soft); color: var(--ink); border-radius: 5px; padding: .1rem .35rem; }
    .usr .pw { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background: var(--warn-soft); color: var(--warn); border-radius: 6px; padding: .1rem .4rem; user-select: all; }
</style>

<div class="usr">
<div class="container">

    <div class="usr-head">
        <div>
            <h1 class="usr-title">Bulk upload</h1>
            <p class="usr-sub">Create or update many eduroam accounts from a CSV or Excel file and e-mail each user their login.</p>
        </div>
        <a href="{{ route('enroll') }}" class="btn-clear">Add a single user instead</a>
    </div>

    @if($errors->any())
        <div class="note bad">
            <i class="fa-solid fa-triangle-exclamation mt-1"></i>
            <ul>
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">

        {{-- Left: how the file should look --}}
        <div class="col-lg-5">
            <div class="panel">
                <div class="panel-head"><span class="ico"><i class="fa-solid fa-table-list"></i></span>File format</div>
                <div class="panel-body">
                    <p class="usr-sub mb-3">The first row must be a header. Column names are not case-sensitive and can be in any order.</p>

                    <table class="tbl cols">
                        <thead><tr><th>Column</th><th>Required</th><th>Notes</th></tr></thead>
                        <tbody>
                            <tr><td><code>givenName</code></td><td>Yes</td><td>First name</td></tr>
                            <tr><td><code>sn</code></td><td>Yes</td><td>Surname</td></tr>
                            <tr><td><code>uid</code></td><td>Yes</td><td>Username: letters, numbers, dot, dash, underscore</td></tr>
                            <tr><td><code>mail</code></td><td>Yes</td><td>Credentials are sent here</td></tr>
                            <tr><td><code>userPassword</code></td><td>No</td><td>Blank = a secure password is generated</td></tr>
                            <tr><td><code>Reg</code></td><td>No</td><td>Registration number, only shown in the e-mail</td></tr>
                            <tr><td><code>type</code></td><td>No</td><td>{{ implode(', ', array_keys($types)) }}. Blank = the type chosen in the form</td></tr>
                        </tbody>
                    </table>

                    <div class="note info mx-0 mb-0 mt-3">
                        <i class="fa-solid fa-circle-info mt-1"></i>
                        <span>
                            An existing username of the same type is updated. One that already exists as a different type is skipped and reported.
                            Up to {{ number_format($maxRows) }} rows per file.
                        </span>
                    </div>

                    <a href="{{ route($sampleRoute) }}" class="btn-clear mt-3"><i class="fa-solid fa-download me-2"></i>Download sample CSV</a>
                </div>
            </div>
        </div>

        {{-- Right: upload form --}}
        <div class="col-lg-7">
            <div class="panel">
                <div class="panel-head"><span class="ico"><i class="fa-solid fa-file-arrow-up"></i></span>Upload file</div>
                <div class="panel-body">

                    <form action="{{ route($uploadRoute) }}" method="POST" enctype="multipart/form-data" id="bulkForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="type">Default user type <span class="hint">(rows without a type)</span></label>
                                <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                    @foreach($types as $key => $t)
                                        <option value="{{ $key }}" @selected(old('type', array_key_first($types)) === $key)>{{ $t['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="campus">Campus <span class="hint">(applies to every row)</span></label>
                                <select name="campus" id="campus" class="form-select @error('campus') is-invalid @enderror" required>
                                    @foreach($campuses as $key => $c)
                                        <option value="{{ $key }}" @selected(old('campus') === $key)>{{ $c['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('campus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="csv_file">File <span class="hint">(.csv, .txt or .xlsx, max 5 MB)</span></label>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt,.xlsx"
                                       class="form-control @error('csv_file') is-invalid @enderror" required>
                                @error('csv_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="invalid-feedback" id="fileError"></div>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn-primary-soft py-2" id="submitBtn">
                                <span class="btn-text">Create accounts and send credentials</span>
                                <span class="btn-loading d-none"><span class="spinner-border spinner-border-sm me-2"></span>Working, please keep this page open...</span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- Results of the last upload --}}
    @if($results->isNotEmpty())
        <div class="panel mt-4" id="results">
            <div class="panel-head">
                <span class="ico"><i class="fa-solid fa-list-check"></i></span>Results
                <span class="count ms-auto">{{ number_format($results->count()) }} row(s)</span>
            </div>

            <div class="panel-body pb-0">
                <div class="stat">
                    <span class="chip ok">{{ $created }} created</span>
                    <span class="chip type-student">{{ $updated }} updated</span>
                    <span class="chip {{ $failed ? 'fail' : 'skip' }}">{{ $failed }} failed / skipped</span>
                    <span class="chip ok">{{ $mailed }} e-mailed</span>
                    @if($mailBad)<span class="chip fail">{{ $mailBad }} e-mail failed</span>@endif
                </div>

                @if($mailBad)
                    <div class="note warn mt-3 mb-0">
                        <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                        <span>Some accounts were saved but their e-mail failed. Their passwords are shown below <strong>once</strong>; copy them now and pass them on securely.</span>
                    </div>
                @endif
            </div>

            <div class="table-responsive mt-3">
                <table class="tbl">
                    <thead>
                        <tr><th>Line</th><th>User</th><th>Type</th><th>LDAP</th><th>E-mail</th><th>Details</th></tr>
                    </thead>
                    <tbody>
                    @foreach($results as $r)
                        @php
                            $ldapClass  = match ($r['ldap']) { 'created' => 'ok', 'updated' => 'type-student', 'failed' => 'fail', default => 'skip' };
                            $mailClass  = match ($r['email']) { 'sent' => 'ok', 'failed' => 'fail', default => 'skip' };
                            $typeKey    = $r['type'] ?? '';
                            $typeLabel  = $types[$typeKey]['label'] ?? ($typeKey !== '' ? ucfirst($typeKey) : '-');
                        @endphp
                        <tr>
                            <td class="mail">{{ $r['line'] ?? '' }}</td>
                            <td>
                                <div class="person">
                                    <div>
                                        <div class="nm">{{ $r['name'] ?: '-' }}</div>
                                        <div class="id">{{ $r['uid'] ?: '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="chip {{ $typeKey !== '' ? 'type-' . $typeKey : '' }}">{{ $typeLabel }}</span></td>
                            <td><span class="chip {{ $ldapClass }}">{{ $r['ldap'] }}</span></td>
                            <td><span class="chip {{ $mailClass }}">{{ $r['email'] }}</span></td>
                            <td>
                                @if(! empty($r['error']))<div class="desc">{{ $r['error'] }}</div>@endif
                                @if(! empty($r['password']))<div class="mt-1">Password: <span class="pw">{{ $r['password'] }}</span></div>@endif
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
@endsection

@push('scripts')
<script>
    const form   = document.getElementById('bulkForm');
    const file   = document.getElementById('csv_file');
    const fileEr = document.getElementById('fileError');
    const btn    = document.getElementById('submitBtn');

    // Quick client-side check so a wrong file doesn't cost a round trip
    function checkFile() {
        const f = file.files[0];
        let msg = '';
        if (f) {
            if (! /\.(csv|txt|xlsx)$/i.test(f.name)) msg = 'Please choose a .csv, .txt or .xlsx file.';
            else if (f.size > 5 * 1024 * 1024)        msg = 'The file is larger than 5 MB.';
        }
        file.classList.toggle('is-invalid', msg !== '');
        fileEr.textContent = msg;
        fileEr.style.display = msg ? 'block' : 'none';
        return msg === '';
    }
    file.addEventListener('change', checkFile);

    // Disable the button and show a spinner while the server works (large files take a while)
    form.addEventListener('submit', e => {
        if (! checkFile()) { e.preventDefault(); return; }
        btn.disabled = true;
        btn.querySelector('.btn-text').classList.add('d-none');
        btn.querySelector('.btn-loading').classList.remove('d-none');
    });

    // Jump to the results after an upload
    @if($results->isNotEmpty())
        document.getElementById('results')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    @endif
</script>
@endpush
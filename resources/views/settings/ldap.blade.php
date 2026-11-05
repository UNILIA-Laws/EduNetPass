@extends('layouts.app')
@section('title', 'MyLDAP | LDAP settings')

@section('content')
@include('partials.usr-style')

{{-- Only what the shared stylesheet does not provide --}}
<style>
    .usr .chip.ok   { background: var(--good-soft); color: var(--good); }
    .usr .chip.fail { background: var(--bad-soft);  color: var(--bad); }
    .usr .tbl td.key { width: 240px; font-weight: 600; white-space: nowrap; }
    .usr .tbl td.val { word-break: break-all; }
    .usr .tbl td.val code { font-size: .82rem; color: var(--ink); background: var(--idle-soft); border-radius: 5px; padding: .1rem .4rem; }
    .usr .panel-head .count { margin-left: auto; }
    .usr .panel-head form { margin-left: auto; }

    /* Connection test column stays in view while you read the settings */
    @media (min-width: 992px) { .usr .sticky-col { position: sticky; top: 1rem; } }

    .usr .test-summary { margin: 1rem 1.1rem 0; display: flex; align-items: center; gap: .6rem; }
    .usr .test-summary .ms { margin-left: auto; font-weight: 600; font-size: .8rem; opacity: .85; white-space: nowrap; }
    .usr .checks { list-style: none; margin: 0; padding: .4rem 1.1rem 1rem; }
    .usr .checks li { display: flex; gap: .75rem; padding: .7rem 0; border-bottom: 1px solid #EEF1F6; }
    .usr .checks li:last-child { border-bottom: 0; }
    .usr .checks .st {
        flex: none; width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center; font-size: .72rem;
    }
    .usr .checks .st.ok   { background: var(--good-soft); color: var(--good); }
    .usr .checks .st.fail { background: var(--bad-soft);  color: var(--bad); }
    .usr .checks .nm { font-weight: 600; font-size: .86rem; line-height: 1.3; }
    .usr .checks .dt { color: var(--muted); font-size: .8rem; word-break: break-word; }
    .usr .test-empty { text-align: center; padding: 2.5rem 1.5rem; color: var(--muted); font-size: .88rem; }
    .usr .test-empty i { display: block; font-size: 1.8rem; margin-bottom: .6rem; color: #C2CAD8; }
    .usr .test-busy { text-align: center; padding: 2.5rem 1.5rem; color: var(--muted); font-size: .88rem; }
    @media (max-width: 575.98px) { .usr .tbl td.key { width: auto; white-space: normal; } }
</style>

<div class="usr">
<div class="container">

    @include('users._alerts')

    <div class="usr-head">
        <div>
            <h1 class="usr-title">LDAP server settings</h1>
            <p class="usr-sub">
                Read from the server's <code>.env</code> file and cannot be changed here.
                To change them, edit <code>.env</code> and run <code>php artisan config:clear</code>.
            </p>
        </div>
        <span class="chip {{ $encrypted ? 'ok' : 'fail' }}">
            <i class="fa-solid {{ $encrypted ? 'fa-lock' : 'fa-lock-open' }} me-1"></i>{{ $encrypted ? 'Encrypted connection' : 'Not encrypted' }}
        </span>
    </div>

    @foreach ($warnings as $w)
        <div class="note warn"><i class="fa-solid fa-triangle-exclamation mt-1"></i><span>{{ $w }}</span></div>
    @endforeach

    <div class="row g-3">

        {{-- Connection test: first on small screens, right-hand column on large ones --}}
        <div class="col-lg-5 order-lg-2">
            <div class="panel sticky-col" id="testPanel">
                <div class="panel-head">
                    <span class="ico"><i class="fa-solid fa-plug"></i></span>Connection test
                    <form method="POST" action="{{ route('settings.ldap.test') }}" id="testForm" class="m-0">
                        @csrf
                        <button type="submit" class="btn-primary-soft" id="testBtn">
                            <i class="fa-solid fa-play me-2"></i><span>Run test</span>
                        </button>
                    </form>
                </div>

                <div id="testResult" aria-live="polite">
                    @if ($test)
                        <div class="note {{ $test['ok'] ? 'good' : 'bad' }} test-summary">
                            <i class="fa-solid {{ $test['ok'] ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                            <strong>{{ $test['ok'] ? 'All checks passed' : 'Problems found' }}</strong>
                            <span class="ms">{{ $test['ms'] }} ms</span>
                        </div>
                        <ul class="checks">
                            @foreach ($test['checks'] as $c)
                                <li>
                                    <span class="st {{ $c['ok'] ? 'ok' : 'fail' }}"><i class="fa-solid {{ $c['ok'] ? 'fa-check' : 'fa-xmark' }}"></i></span>
                                    <div>
                                        <div class="nm">{{ $c['name'] }}</div>
                                        @if (!empty($c['detail']))<div class="dt">{{ $c['detail'] }}</div>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="test-empty">
                            <i class="fa-solid fa-circle-nodes"></i>
                            Not tested yet. Run the test to check that the server is reachable and the credentials work.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Configuration --}}
        <div class="col-lg-7 order-lg-1">
            <div class="panel">
                <div class="panel-head">
                    <span class="ico"><i class="fa-solid fa-server"></i></span>Configuration
                    <span class="count">{{ count($settings) + 1 }} settings</span>
                </div>
                <div class="table-responsive">
                    <table class="tbl">
                        <tbody>
                        @foreach ($settings as $label => $value)
                            <tr>
                                <td class="key">{{ $label }}</td>
                                <td class="val">
                                    @if ($value === null || $value === '')
                                        <span class="desc">-</span>
                                    @else
                                        <code>{{ $value }}</code>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                            <tr>
                                <td class="key">Encrypted connection</td>
                                <td class="val"><span class="chip {{ $encrypted ? 'ok' : 'fail' }}">{{ $encrypted ? 'Yes' : 'No' }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    // Run the test in place: no reload, no jumping to the bottom of the page.
    // If anything goes wrong, fall back to a normal form submit.
    (function () {
        const form   = document.getElementById('testForm');
        const btn    = document.getElementById('testBtn');
        const result = document.getElementById('testResult');
        const label  = btn.querySelector('span');
        const icon   = btn.querySelector('i');

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            btn.disabled = true;
            label.textContent = 'Testing…';
            icon.className = 'fa-solid fa-spinner fa-spin me-2';
            result.innerHTML = '<div class="test-busy"><i class="fa-solid fa-spinner fa-spin me-2"></i>Contacting the LDAP server…</div>';

            try {
                const res  = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const html = await res.text();
                const doc  = new DOMParser().parseFromString(html, 'text/html');
                const next = doc.getElementById('testResult');
                if (!res.ok || !next) { form.submit(); return; }

                result.innerHTML = next.innerHTML;
                if (window.innerWidth < 992) {
                    document.getElementById('testPanel').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } catch (err) {
                form.submit();
                return;
            }

            btn.disabled = false;
            label.textContent = 'Run again';
            icon.className = 'fa-solid fa-rotate-right me-2';
        });
    })();
</script>
@endpush
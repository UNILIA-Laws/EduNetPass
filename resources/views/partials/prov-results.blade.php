{{-- resources/views/partials/prov-results.blade.php  (shared by enroll + bulk views) --}}
@php
    $results = collect(session('results', []));
    $badge = fn($v) => match($v) {
        'created', 'sent' => 'ok', 'updated' => 'info', 'failed' => 'bad', default => 'idle',
    };
    $ldapCount  = $results->whereIn('ldap', ['created', 'updated'])->count();
    $mailCount  = $results->where('email', 'sent')->count();
    $errorCount = $results->filter(fn($r) => $r['error'])->count();
@endphp

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
                <thead><tr><th>#</th><th>Username</th><th>User</th><th>LDAP</th><th>E-mail</th><th>Notes</th></tr></thead>
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
                                <div>Give the user this password manually: <code>{{ $r['password'] }}</code></div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

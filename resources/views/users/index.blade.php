@extends('layouts.app')
@section('title', 'eduroam | Users')

@section('content')
<style>
    .usr, .usr-modal {
        --ink: #172033;
        --muted: #667085;
        --line: #E3E8F0;
        --surface: #FFFFFF;
        --canvas: #F3F5F9;
        --accent: #2557D6;
        --accent-ink: #1B44AB;
        --accent-soft: #E8EEFC;
        --warn: #A8620B;  --warn-soft: #FDF1DF;
        --bad: #C93A3A;   --bad-soft: #FCE9E9;
        --idle-soft: #EEF1F5;
        color: var(--ink);
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    .usr {
        background: var(--canvas);
        padding: 2rem 0 3rem;
    }
    .usr :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

    /* Heading */
    .usr-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
    .usr-title { font-size: 1.6rem; font-weight: 700; letter-spacing: -.02em; margin: 0 0 .2rem; }
    .usr-sub { color: var(--muted); font-size: .92rem; margin: 0; }
    .count {
        font-size: .82rem; font-weight: 600; color: var(--accent-ink); background: var(--accent-soft);
        padding: .3rem .8rem; border-radius: 999px;
    }

    /* Panel */
    .panel {
        background: var(--surface); border: 1px solid var(--line); border-radius: 16px;
        box-shadow: 0 1px 2px rgba(23,32,51,.04), 0 8px 24px -12px rgba(23,32,51,.12);
        overflow: hidden;
    }

    /* Toolbar */
    .toolbar { display: flex; flex-wrap: wrap; gap: .6rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--line); }
    .toolbar .search { position: relative; flex: 1 1 260px; }
    .toolbar .search i { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); color: #98A2B3; font-size: .85rem; pointer-events: none; }
    .toolbar .search .form-control { padding-left: 2.2rem; }
    .toolbar .form-select { flex: 0 1 200px; }
    .usr .form-control, .usr .form-select, .usr-modal .form-control {
        font-size: .88rem; border: 1px solid #D5DBE6; border-radius: 8px; padding: .5rem .75rem; background-color: #fff; color: var(--ink);
        transition: border-color .15s, box-shadow .15s;
    }
    .usr .form-control::placeholder, .usr-modal .form-control::placeholder { color: #A5AEBD; }
    .usr .form-control:focus, .usr .form-select:focus, .usr-modal .form-control:focus {
        border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,87,214,.15);
    }
    .btn-primary-soft {
        background: var(--accent); color: #fff; border: 0; border-radius: 8px; font-size: .86rem; font-weight: 600; padding: .5rem 1rem;
        transition: background .15s;
    }
    .btn-primary-soft:hover { background: var(--accent-ink); color: #fff; }
    .btn-clear {
        font-size: .86rem; font-weight: 600; color: var(--muted); background: transparent; border: 1px solid #D5DBE6;
        border-radius: 8px; padding: .5rem .9rem; text-decoration: none; display: inline-flex; align-items: center;
    }
    .btn-clear:hover { background: var(--idle-soft); color: var(--ink); }

    /* Notices */
    .note {
        display: flex; gap: .55rem; align-items: flex-start; border-radius: 10px; padding: .6rem .85rem; font-size: .84rem; margin-bottom: 1rem;
    }
    .note.bad  { background: var(--bad-soft); color: #8E2323; }
    .note.info { background: var(--accent-soft); color: var(--accent-ink); margin: 1rem 1.1rem 0; }

    /* Table */
    .tbl { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .86rem; margin: 0; }
    .tbl th {
        background: #F7F9FC; text-align: left; font-weight: 600; font-size: .78rem; color: var(--muted);
        padding: .65rem 1.1rem; border-bottom: 1px solid var(--line); white-space: nowrap;
    }
    .tbl td { padding: .75rem .9rem; border-bottom: 1px solid #EEF1F6; vertical-align: middle; }
    .tbl th { padding-left: .9rem; padding-right: .9rem; }
    .tbl td.mail { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tbl tbody tr:last-child td { border-bottom: 0; }
    .tbl tbody tr:hover td { background: #FAFBFD; }

    .person { display: flex; align-items: center; gap: .75rem; min-width: 150px; }
    .avatar {
        flex: none; width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center;
        background: var(--accent-soft); color: var(--accent-ink); font-size: .75rem; font-weight: 700;
    }
    .person .nm { font-weight: 600; line-height: 1.25; }
    .person .id { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .76rem; color: var(--muted); }
    .mail { color: var(--muted); }
    .chip { display: inline-block; font-size: .76rem; font-weight: 600; background: var(--idle-soft); color: #475467; padding: .15rem .6rem; border-radius: 999px; }

    /* Row actions */
    .acts { display: flex; justify-content: flex-end; gap: .4rem; white-space: nowrap; }
    .act {
        display: inline-flex; align-items: center; gap: .35rem; font-size: .8rem; font-weight: 600; border: 0; border-radius: 8px;
        padding: .35rem .7rem; text-decoration: none; transition: background .15s;
    }
    .act.edit  { background: var(--accent-soft); color: var(--accent-ink); }
    .act.edit:hover  { background: #D8E3FA; color: var(--accent-ink); }
    .act.reset { background: var(--warn-soft); color: var(--warn); }
    .act.reset:hover { background: #FAE5C4; color: var(--warn); }
    .act.del   { background: var(--bad-soft); color: var(--bad); }
    .act.del:hover   { background: #F8D4D4; color: var(--bad); }

    .empty { text-align: center; padding: 3rem 1rem !important; color: var(--muted); }
    .empty i { display: block; font-size: 1.6rem; margin-bottom: .6rem; color: #C2CAD8; }

    .pager { padding: .9rem 1.1rem; border-top: 1px solid var(--line); }
    .pager:empty { display: none; }
    .pager .pagination { margin: 0; justify-content: center; --bs-pagination-active-bg: var(--accent); --bs-pagination-active-border-color: var(--accent); --bs-pagination-color: var(--accent-ink); }

    /* Modal */
    .usr-modal .modal-content { border: 0; border-radius: 16px; box-shadow: 0 20px 50px -12px rgba(23,32,51,.35); }
    .usr-modal .modal-header { border-bottom: 0; padding: 1.25rem 1.4rem .25rem; }
    .usr-modal .modal-title { font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: .6rem; }
    .usr-modal .modal-title .ico {
        width: 32px; height: 32px; border-radius: 9px; display: grid; place-items: center; background: var(--warn-soft); color: var(--warn); font-size: .85rem;
    }
    .usr-modal .modal-body { padding: .5rem 1.4rem 1rem; font-size: .9rem; }
    .usr-modal .modal-body p { color: #475467; line-height: 1.5; }
    .usr-modal .modal-body code { background: var(--idle-soft); color: var(--ink); border-radius: 5px; padding: .1rem .35rem; }
    .usr-modal .form-label { font-size: .8rem; font-weight: 600; margin-bottom: .3rem; }
    .usr-modal .form-label .hint { color: var(--muted); font-weight: 400; }
    .usr-modal .form-check-input:checked { background-color: var(--accent); border-color: var(--accent); }
    .usr-modal .form-check-label { font-size: .86rem; color: #475467; }
    .usr-modal .modal-footer { border-top: 0; padding: .25rem 1.4rem 1.25rem; gap: .5rem; }
    .usr-modal .btn-cancel { background: var(--idle-soft); color: #475467; border: 0; border-radius: 8px; font-size: .86rem; font-weight: 600; padding: .5rem 1rem; }
    .usr-modal .btn-cancel:hover { background: #E3E8F0; }
    .usr-modal .modal-title .ico.danger { background: var(--bad-soft); color: var(--bad); }
    .btn-danger-soft {
        background: var(--bad); color: #fff; border: 0; border-radius: 8px; font-size: .86rem; font-weight: 600; padding: .5rem 1rem;
        transition: background .15s;
    }
    .btn-danger-soft:hover { background: #A82E2E; color: #fff; }
</style>

<div class="usr">
<div class="container">

    @include('users._alerts')

    @if($error)
        <div class="note bad"><i class="fa-solid fa-triangle-exclamation mt-1"></i><span>Could not read from LDAP: {{ $error }}</span></div>
    @endif

    <div class="usr-head">
        <div>
            <h1 class="usr-title">Users</h1>
            <p class="usr-sub">Search, edit and reset passwords for eduroam accounts in LDAP.</p>
        </div>
        <span class="count">{{ number_format($users->total()) }} found</span>
    </div>

    <div class="panel">

        <form method="GET" action="{{ route('users.index') }}" class="toolbar">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search by username, name or e-mail" aria-label="Search users" autofocus>
            </div>
            <select name="campus" class="form-select" aria-label="Campus">
                <option value="">All campuses</option>
                @foreach($campuses as $key => $c)
                    <option value="{{ $key }}" @selected($campusKey === $key)>{{ $c['label'] }}</option>
                @endforeach
            </select>
            <button class="btn-primary-soft">Search</button>
            @if($q !== '' || $campusKey !== '')
                <a href="{{ route('users.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>

        @if($capped)
            <div class="note info"><i class="fa-solid fa-circle-info mt-1"></i><span>Showing the first {{ number_format(config('ldap.max_list')) }} matches. Use the search box to narrow down.</span></div>
        @endif

        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr><th>User</th><th>E-mail</th><th>Campus</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($users as $u)
                    @php
                        $campusLabel = collect($campuses)->first(fn ($c) => strtolower(preg_replace('/\s*,\s*/', ',', $c['dn'])) === strtolower(preg_replace('/\s*,\s*/', ',', $u['campusDn'])))['label'] ?? '-';
                        $initials = collect(preg_split('/\s+/', trim($u['name'] ?? '')))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('') ?: '?';
                    @endphp
                    <tr>
                        <td>
                            <div class="person">
                                <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                                <div>
                                    <div class="nm">{{ $u['name'] }}</div>
                                    <div class="id">{{ $u['uid'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="mail">{{ $u['mail'] ?: '-' }}</td>
                        <td><span class="chip">{{ $campusLabel }}</span></td>
                        <td>
                            <div class="acts">
                                <a href="{{ route('users.edit', $u['uid']) }}" class="act edit"><i class="fa-solid fa-pen"></i>Edit</a>
                                <button type="button" class="act reset js-reset" data-uid="{{ $u['uid'] }}" data-name="{{ $u['name'] }}" title="Reset password" aria-label="Reset password for {{ $u['uid'] }}">
                                    <i class="fa-solid fa-key"></i><span class="d-none d-xxl-inline">Reset</span>
                                </button>
                                <button type="button" class="act del js-delete" data-uid="{{ $u['uid'] }}" data-name="{{ $u['name'] }}" title="Delete user" aria-label="Delete {{ $u['uid'] }}">
                                    <i class="fa-solid fa-trash"></i><span class="d-none d-xxl-inline">Delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty"><i class="fa-solid fa-user-slash"></i>No users found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $users->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
</div>

{{-- Reset password modal (one modal, reused for every row) --}}
<div class="modal fade usr-modal" id="resetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="resetForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><span class="ico"><i class="fa-solid fa-key"></i></span>Reset password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Reset the password for <strong id="resetName"></strong> (<code id="resetUid"></code>)? The old password will stop working immediately.</p>
                <label class="form-label" for="resetPwd">New password <span class="hint">(blank = generate)</span></label>
                <input type="text" name="userPassword" id="resetPwd" class="form-control mb-3" minlength="8" autocomplete="off">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="send_email" value="1" id="sendEmail" checked>
                    <label class="form-check-label" for="sendEmail">E-mail the new password to the student</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-primary-soft px-4">Reset password</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete confirmation modal --}}
<div class="modal fade usr-modal" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="deleteForm" class="modal-content">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h5 class="modal-title"><span class="ico danger"><i class="fa-solid fa-trash"></i></span>Delete user</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteName"></strong> (<code id="deleteUid"></code>)?</p>
                <p class="mb-0">The account will be removed from LDAP and the student will lose eduroam access immediately. This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-danger-soft px-4">Yes, delete</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const urlTemplate = @json(route('users.reset', ['uid' => '__UID__']));
    const modal = new bootstrap.Modal(document.getElementById('resetModal'));

    document.querySelectorAll('.js-reset').forEach(btn => btn.addEventListener('click', () => {
        document.getElementById('resetForm').action = urlTemplate.replace('__UID__', encodeURIComponent(btn.dataset.uid));
        document.getElementById('resetName').textContent = btn.dataset.name;
        document.getElementById('resetUid').textContent = btn.dataset.uid;
        modal.show();
    }));

    const deleteUrlTemplate = @json(route('users.destroy', ['uid' => '__UID__']));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    document.querySelectorAll('.js-delete').forEach(btn => btn.addEventListener('click', () => {
        document.getElementById('deleteForm').action = deleteUrlTemplate.replace('__UID__', encodeURIComponent(btn.dataset.uid));
        document.getElementById('deleteName').textContent = btn.dataset.name;
        document.getElementById('deleteUid').textContent = btn.dataset.uid;
        deleteModal.show();
    }));
</script>
@endpush
@extends('layouts.app')
@section('title', 'eduroam | Users')

@section('content')
@include('partials.usr-style')

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
            <select name="type" class="form-select" aria-label="User type">
                <option value="">All types</option>
                @foreach($types as $key => $t)
                    <option value="{{ $key }}" @selected($typeKey === $key)>{{ $t['label'] }}</option>
                @endforeach
            </select>
            <select name="campus" class="form-select" aria-label="Campus">
                <option value="">All campuses</option>
                @foreach($campuses as $key => $c)
                    <option value="{{ $key }}" @selected($campusKey === $key)>{{ $c['label'] }}</option>
                @endforeach
            </select>
            <button class="btn-primary-soft">Search</button>
            @if($q !== '' || $campusKey !== '' || $typeKey !== '')
                <a href="{{ route('users.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>

        @if($capped)
            <div class="note info"><i class="fa-solid fa-circle-info mt-1"></i><span>Showing the first {{ number_format(config('ldap.max_list')) }} matches. Use the search box to narrow down.</span></div>
        @endif

        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr><th>User</th><th>E-mail</th><th>Type</th><th>Campus</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($users as $u)
                    @php
                        $campusLabel = collect($campuses)->first(fn ($c) => strtolower(preg_replace('/\s*,\s*/', ',', $c['dn'])) === strtolower(preg_replace('/\s*,\s*/', ',', $u['campusDn'])))['label'] ?? '-';
                        $initials = collect(preg_split('/\s+/', trim($u['name'] ?? '')))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('') ?: '?';
                        $typeLabel = $u['type'] ? ($types[$u['type']]['label'] ?? ucfirst($u['type'])) : 'Other';
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
                        <td>
                            <span class="chip {{ $u['type'] ? 'type-' . $u['type'] : '' }}"
                                  @if(! $u['type']) title="Affiliations: {{ implode(', ', $u['affiliations']) ?: 'none' }}" @endif>
                                {{ $typeLabel }}
                            </span>
                        </td>
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
                    <tr><td colspan="5" class="empty"><i class="fa-solid fa-user-slash"></i>No users found.</td></tr>
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
                    <label class="form-check-label" for="sendEmail">E-mail the new password to the user</label>
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
                <p class="mb-0">The account will be removed from LDAP and the user will lose eduroam access immediately. This cannot be undone.</p>
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
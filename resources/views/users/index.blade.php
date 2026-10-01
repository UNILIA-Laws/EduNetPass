@extends('layouts.app')
@section('title', 'eduroam | Users')

@section('content')
<div class="container py-5">

    @include('users._alerts')

    @if($error)
        <div class="alert alert-danger border-0 shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i>Could not read from LDAP: {{ $error }}</div>
    @endif

    <div class="card shadow-lg">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-users me-2"></i>Users in LDAP</h5>
            <span class="badge text-bg-light">{{ $users->total() }} found</span>
        </div>
        <div class="card-body p-4">

            <form method="GET" action="{{ route('users.index') }}" class="row g-2 mb-4">
                <div class="col-md-6">
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search by username, name or e-mail" autofocus>
                </div>
                <div class="col-md-3">
                    <select name="campus" class="form-select">
                        <option value="">All campuses</option>
                        @foreach($campuses as $key => $c)
                            <option value="{{ $key }}" @selected($campusKey === $key)>{{ $c['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-grid d-md-flex gap-2">
                    <button class="btn btn-eduroam flex-fill"><i class="fa-solid fa-magnifying-glass me-1"></i>Search</button>
                    @if($q !== '' || $campusKey !== '')
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>

            @if($capped)
                <div class="alert alert-info small">Showing the first {{ number_format(config('ldap.max_list')) }} matches. Use the search box to narrow down.</div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>Username</th><th>Name</th><th>E-mail</th><th>Campus</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                    @forelse($users as $u)
                        @php
                            $campusLabel = collect($campuses)->first(fn ($c) => strtolower(preg_replace('/\s*,\s*/', ',', $c['dn'])) === strtolower(preg_replace('/\s*,\s*/', ',', $u['campusDn'])))['label'] ?? '-';
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $u['uid'] }}</td>
                            <td>{{ $u['name'] }}</td>
                            <td>{{ $u['mail'] ?: '-' }}</td>
                            <td>{{ $campusLabel }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('users.edit', $u['uid']) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Edit</a>
                                <button type="button" class="btn btn-sm btn-outline-warning js-reset"
                                        data-uid="{{ $u['uid'] }}" data-name="{{ $u['name'] }}">
                                    <i class="fa-solid fa-key me-1"></i>Reset password
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No users found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</div>

{{-- Reset password modal (one modal, reused for every row) --}}
<div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="resetForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-key me-2"></i>Reset password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Reset the password for <strong id="resetName"></strong> (<code id="resetUid"></code>)? The old password will stop working immediately.</p>
                <label class="form-label small fw-bold">New password <span class="text-muted fw-normal">(blank = auto-generate)</span></label>
                <input type="text" name="userPassword" class="form-control mb-3" minlength="8" autocomplete="off">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="send_email" value="1" id="sendEmail" checked>
                    <label class="form-check-label" for="sendEmail">E-mail the new password to the student</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-eduroam px-4">Reset password</button>
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
</script>
@endpush
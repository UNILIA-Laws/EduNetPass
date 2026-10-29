@extends('layouts.app')
@section('title', 'eduroam | Staff')

@section('content')
@include('partials.usr-style')

<div class="usr">
<div class="container">

    <div class="usr-head">
        <div>
            <h1 class="usr-title">Staff</h1>
            <p class="usr-sub">Manage who can sign in to this portal.</p>
        </div>
        <span class="count">{{ number_format($staff->total()) }} {{ \Illuminate\Support\Str::plural('account', $staff->total()) }}</span>
    </div>


    @if(session('status'))
        <div class="note good"><i class="fa-solid fa-circle-check mt-1"></i><span>{{ session('status') }}</span></div>
    @endif
    @if($errors->has('staff'))
        <div class="note bad"><i class="fa-solid fa-triangle-exclamation mt-1"></i><span>{{ $errors->first('staff') }}</span></div>
    @endif

    <div class="panel">

        <form method="GET" action="{{ route('staff.index') }}" class="toolbar">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search name or e-mail" aria-label="Search staff">
            </div>
            <button class="btn-primary-soft">Search</button>
            @if($q !== '')
                <a href="{{ route('staff.index') }}" class="btn-clear">Clear</a>
            @endif
            <a href="{{ route('staff.create') }}" class="btn-primary-soft text-decoration-none d-inline-flex align-items-center ms-auto">
                <i class="fa-solid fa-plus me-2"></i>Add staff
            </a>
        </form>

        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr><th>Name</th><th>Role</th><th>Campus</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($staff as $s)
                    @php
                        $initials = collect(preg_split('/\s+/', trim($s->name ?? '')))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('') ?: '?';
                        $isMe = $s->is(auth()->user());
                    @endphp
                    <tr>
                        <td>
                            <div class="person">
                                <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                                <div>
                                    <div class="nm">
                                        {{ $s->name }}
                                        @if($isMe)<span class="desc fw-normal">(you)</span>@endif
                                    </div>
                                    <div class="id">{{ $s->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="chip {{ $s->is_admin ? 'type-faculty' : '' }}">{{ $s->is_admin ? 'Administrator' : 'Staff' }}</span>
                        </td>
                        <td><span class="chip">{{ $s->campus?->label ?? 'All campuses' }}</span></td>
                        <td>
                            <div class="acts">
                                <a href="{{ route('staff.edit', $s) }}" class="act edit"><i class="fa-solid fa-pen"></i>Edit</a>
                                @unless($isMe)
                                    <button type="button" class="act del js-delete"
                                            data-action="{{ route('staff.destroy', $s) }}" data-name="{{ $s->name }}" data-email="{{ $s->email }}"
                                            title="Delete staff" aria-label="Delete {{ $s->name }}">
                                        <i class="fa-solid fa-trash"></i><span class="d-none d-xxl-inline">Delete</span>
                                    </button>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty"><i class="fa-solid fa-user-slash"></i>No staff found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $staff->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
</div>

{{-- Delete confirmation modal (one modal, reused for every row) --}}
<div class="modal fade usr-modal" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="deleteForm" class="modal-content">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h5 class="modal-title"><span class="ico danger"><i class="fa-solid fa-trash"></i></span>Delete staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteName"></strong> (<code id="deleteEmail"></code>)?</p>
                <p class="mb-0">They will no longer be able to sign in to this portal. This cannot be undone.</p>
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
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    document.querySelectorAll('.js-delete').forEach(btn => btn.addEventListener('click', () => {
        document.getElementById('deleteForm').action = btn.dataset.action;
        document.getElementById('deleteName').textContent = btn.dataset.name;
        document.getElementById('deleteEmail').textContent = btn.dataset.email;
        deleteModal.show();
    }));
</script>
@endpush
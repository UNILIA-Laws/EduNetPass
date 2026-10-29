@extends('layouts.app')
@section('title', 'eduroam | ' . $group['cn'])

@section('content')
@include('partials.usr-style')

<div class="usr">
<div class="container">

    @if(session('status'))
        <div class="note good"><i class="fa-solid fa-circle-check mt-1"></i><span>{{ session('status') }}</span></div>
    @endif
    @if($errors->any())
        <div class="note bad">
            <i class="fa-solid fa-triangle-exclamation mt-1"></i>
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="usr-head">
        <div>
            <a href="{{ route('groups.index') }}" class="text-decoration-none small" style="color:var(--accent-ink);font-weight:600">
                <i class="fa-solid fa-arrow-left me-1"></i>All groups
            </a>
            <h1 class="usr-title mt-2">{{ $group['cn'] }}</h1>
            <p class="usr-sub">{{ $group['description'] ?: 'No description.' }}</p>
        </div>
        <button type="button" class="act del px-3 py-2 js-delete-group" data-name="{{ $group['cn'] }}">
            <i class="fa-solid fa-trash"></i>Delete group
        </button>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-head">
                    <span class="ico"><i class="fa-solid fa-users"></i></span>Members
                    <span class="count ms-auto">{{ number_format($group['count']) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="tbl">
                        <thead>
                            <tr><th>Username</th><th>Name</th><th>E-mail</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                        @forelse($members as $m)
                            <tr>
                                <td>
                                    @if($m['user'])
                                        <a href="{{ route('users.edit', $m['uid']) }}" class="text-decoration-none" style="font-weight:600;color:var(--accent-ink)">{{ $m['uid'] }}</a>
                                    @else
                                        {{ $m['uid'] }} <span class="chip type-guest ms-1">Not found</span>
                                    @endif
                                </td>
                                <td>{{ $m['user']['name'] ?? '-' }}</td>
                                <td class="mail">{{ $m['user']['mail'] ?? '-' }}</td>
                                <td>
                                    <div class="acts">
                                        <button type="button" class="act reset js-remove" data-uid="{{ $m['uid'] }}" title="Remove from group" aria-label="Remove {{ $m['uid'] }} from this group">
                                            <i class="fa-solid fa-user-minus"></i><span class="d-none d-xxl-inline">Remove</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty"><i class="fa-solid fa-user-slash"></i>This group has no members.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-head"><span class="ico"><i class="fa-solid fa-user-plus"></i></span>Add member</div>
                <form method="POST" action="{{ route('groups.members.store', $group['cn']) }}" class="panel-body">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="uid">Username <span class="hint">(uid)</span></label>
                        <input id="uid" name="uid" value="{{ old('uid') }}" class="form-control @error('uid') is-invalid @enderror" required>
                        @error('uid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn-primary-soft w-100" type="submit"><i class="fa-solid fa-user-plus me-1"></i>Add</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

{{-- Delete group modal --}}
<div class="modal fade usr-modal" id="deleteGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('groups.destroy', $group['cn']) }}" class="modal-content">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h5 class="modal-title"><span class="ico danger"><i class="fa-solid fa-trash"></i></span>Delete group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong>{{ $group['cn'] }}</strong>?</p>
                <p class="mb-0">Its users are not deleted. This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-danger-soft px-4">Yes, delete</button>
            </div>
        </form>
    </div>
</div>

{{-- Remove member modal (one modal, reused for every row) --}}
<div class="modal fade usr-modal" id="removeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="removeForm" class="modal-content">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h5 class="modal-title"><span class="ico"><i class="fa-solid fa-user-minus"></i></span>Remove member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Remove <code id="removeUid"></code> from <strong>{{ $group['cn'] }}</strong>? The user account is not deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-primary-soft px-4">Remove</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const deleteGroupModal = new bootstrap.Modal(document.getElementById('deleteGroupModal'));
    document.querySelector('.js-delete-group').addEventListener('click', () => deleteGroupModal.show());

    const removeUrlTemplate = @json(route('groups.members.destroy', [$group['cn'], '__UID__']));
    const removeModal = new bootstrap.Modal(document.getElementById('removeModal'));

    document.querySelectorAll('.js-remove').forEach(btn => btn.addEventListener('click', () => {
        document.getElementById('removeForm').action = removeUrlTemplate.replace('__UID__', encodeURIComponent(btn.dataset.uid));
        document.getElementById('removeUid').textContent = btn.dataset.uid;
        removeModal.show();
    }));
</script>
@endpush
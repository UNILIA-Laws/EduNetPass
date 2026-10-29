@extends('layouts.app')
@section('title', 'eduroam | Campuses')

@section('content')
@include('partials.usr-style')

<div class="usr">
<div class="container">

    @include('users._alerts')

    @if($error)
        <div class="note bad"><i class="fa-solid fa-triangle-exclamation mt-1"></i><span>Could not read campuses from LDAP: {{ $error }}</span></div>
    @endif

    <div class="usr-head">
        <div>
            <h1 class="usr-title">Campuses</h1>
            <p class="usr-sub">Each campus is an organizational unit under <code>{{ $container }}</code>.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="count">{{ count($campuses) }} campus{{ count($campuses) === 1 ? '' : 'es' }}</span>
            <a href="{{ route('campuses.create') }}" class="btn-primary-soft text-decoration-none"><i class="fa-solid fa-plus me-2"></i>Add campus</a>
        </div>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr><th>Campus</th><th>Description</th><th>Users</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($campuses as $c)
                    <tr>
                        <td>
                            <div class="ou">
                                <span class="ou-ico root"><i class="fa-solid fa-building-columns"></i></span>
                                <div>
                                    <div class="nm">{{ $c['label'] }}</div>
                                    <div class="dn">{{ $c['dn'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="desc">{{ $c['description'] ?: '-' }}</td>
                        <td>
                            <a href="{{ route('users.index', ['campus' => $c['key']]) }}" class="chip campus text-decoration-none">
                                {{ number_format($c['users']) }} user{{ $c['users'] === 1 ? '' : 's' }}
                            </a>
                        </td>
                        <td>
                            <div class="acts">
                                <a href="{{ route('campuses.edit', $c['key']) }}" class="act edit"><i class="fa-solid fa-pen"></i>Edit</a>
                                @if($c['users'] > 0)
                                    <span class="lock" title="Move its users to another campus before deleting"><i class="fa-solid fa-lock"></i>In use</span>
                                @else
                                    <button type="button" class="act del js-delete" data-key="{{ $c['key'] }}" data-name="{{ $c['label'] }}" aria-label="Delete {{ $c['label'] }}">
                                        <i class="fa-solid fa-trash"></i>Delete
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty"><i class="fa-solid fa-building-circle-xmark"></i>No campuses yet. Add the first one.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

{{-- Delete confirmation modal --}}
<div class="modal fade usr-modal" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="deleteForm" class="modal-content">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h5 class="modal-title"><span class="ico danger"><i class="fa-solid fa-trash"></i></span>Delete campus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Delete the campus <strong id="deleteName"></strong>?</p>
                <p class="mb-0">The organizational unit is removed from LDAP. It has no users, so no accounts are affected. This cannot be undone.</p>
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
    const deleteUrlTemplate = @json(route('campuses.destroy', ['campus' => '__KEY__']));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    document.querySelectorAll('.js-delete').forEach(btn => btn.addEventListener('click', () => {
        document.getElementById('deleteForm').action = deleteUrlTemplate.replace('__KEY__', encodeURIComponent(btn.dataset.key));
        document.getElementById('deleteName').textContent = btn.dataset.name;
        deleteModal.show();
    }));
</script>
@endpush
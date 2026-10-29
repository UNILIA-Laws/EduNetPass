@extends('layouts.app')
@section('title', 'eduroam | Organizational units')

@section('content')
@include('partials.usr-style')

<div class="usr">
<div class="container">

    @if(session('status'))
        <div class="note good"><i class="fa-solid fa-circle-check mt-1"></i><span>{{ session('status') }}</span></div>
    @endif
    @if($error)
        <div class="note bad"><i class="fa-solid fa-triangle-exclamation mt-1"></i><span>Could not read from LDAP: {{ $error }}</span></div>
    @endif
    @if($errors->any())
        <div class="note bad">
            <i class="fa-solid fa-triangle-exclamation mt-1"></i>
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="usr-head">
        <div>
            <h1 class="usr-title">Organizational units</h1>
            <p class="usr-sub">Browse, create and remove the OUs that organize eduroam accounts in LDAP.</p>
        </div>
        <span class="count">{{ number_format(count($ous)) }} {{ \Illuminate\Support\Str::plural('unit', count($ous)) }}</span>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="panel">
                <div class="table-responsive">
                    <table class="tbl">
                        <thead>
                            <tr><th>OU</th><th>Description</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                        @forelse($ous as $o)
                            <tr>
                                <td style="padding-left: {{ 0.9 + $o['depth'] * 1.5 }}rem">
                                    <div class="ou">
                                        <span class="ou-ico {{ $o['depth'] ? '' : 'root' }}" aria-hidden="true">
                                            <i class="fa-solid fa-folder{{ $o['depth'] ? '' : '-tree' }}"></i>
                                        </span>
                                        <div>
                                            <div class="nm">
                                                {{ $o['name'] }}
                                                @if($o['campus'])<span class="chip campus">Campus</span>@endif
                                            </div>
                                            <div class="dn">{{ $o['dn'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="desc">{{ $o['description'] ?: '-' }}</td>
                                <td>
                                    <div class="acts">
                                        @if($o['locked'])
                                            <span class="lock" title="In use by the application"><i class="fa-solid fa-lock"></i>In use</span>
                                        @else
                                            <button type="button" class="act del js-delete" data-dn="{{ $o['dn'] }}" data-name="{{ $o['name'] }}" title="Delete OU" aria-label="Delete OU {{ $o['name'] }}">
                                                <i class="fa-solid fa-trash"></i><span class="d-none d-xxl-inline">Delete</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty"><i class="fa-solid fa-folder-open"></i>No organizational units found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-head"><span class="ico"><i class="fa-solid fa-plus"></i></span>Create OU</div>
                <form method="POST" action="{{ route('ous.store') }}" class="panel-body">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="name">Name</label>
                        <input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="parent">Parent</label>
                        <select id="parent" name="parent" class="form-select" required>
                            <option value="{{ $baseDn }}" @selected(old('parent') === $baseDn)>(root) {{ $baseDn }}</option>
                            @foreach($ous as $o)
                                <option value="{{ $o['dn'] }}" @selected(old('parent') === $o['dn'])>
                                    {{ str_repeat('— ', $o['depth']) }}{{ $o['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <input id="description" name="description" value="{{ old('description') }}" class="form-control">
                    </div>
                    <button class="btn-primary-soft w-100" type="submit"><i class="fa-solid fa-plus me-1"></i>Create</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

{{-- Delete confirmation modal (one modal, reused for every row) --}}
<div class="modal fade usr-modal" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('ous.destroy') }}" id="deleteForm" class="modal-content">
            @csrf
            @method('DELETE')
            <input type="hidden" name="dn" id="deleteDnInput">
            <div class="modal-header">
                <h5 class="modal-title"><span class="ico danger"><i class="fa-solid fa-trash"></i></span>Delete OU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteName"></strong>?</p>
                <p><code id="deleteDn"></code></p>
                <p class="mb-0">The OU must be empty. This cannot be undone.</p>
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
        document.getElementById('deleteDnInput').value = btn.dataset.dn;
        document.getElementById('deleteName').textContent = btn.dataset.name;
        document.getElementById('deleteDn').textContent = btn.dataset.dn;
        deleteModal.show();
    }));
</script>
@endpush
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('groups.index') }}" class="text-decoration-none small"><i class="fa-solid fa-arrow-left me-1"></i>All groups</a>
            <h1 class="h4 m-0 mt-1">{{ $group['cn'] }}</h1>
            @if($group['description'])<div class="text-muted">{{ $group['description'] }}</div>@endif
        </div>
        <form method="POST" action="{{ route('groups.destroy', $group['cn']) }}"
              onsubmit="return confirm('Delete group {{ addslashes($group['cn']) }}? Its users are not deleted.')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-trash me-1"></i>Delete group</button>
        </form>
    </div>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header fw-semibold">Members ({{ $group['count'] }})</div>
                <div class="table-responsive">
                    <table class="table align-middle m-0">
                        <thead><tr><th>Username</th><th>Name</th><th>E-mail</th><th></th></tr></thead>
                        <tbody>
                        @forelse($members as $m)
                            <tr>
                                <td>
                                    @if($m['user'])
                                        <a href="{{ route('users.edit', $m['uid']) }}" class="text-decoration-none">{{ $m['uid'] }}</a>
                                    @else
                                        {{ $m['uid'] }} <span class="badge text-bg-warning">not found</span>
                                    @endif
                                </td>
                                <td>{{ $m['user']['name'] ?? '—' }}</td>
                                <td class="text-muted">{{ $m['user']['mail'] ?? '—' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('groups.members.destroy', [$group['cn'], $m['uid']]) }}"
                                          onsubmit="return confirm('Remove {{ addslashes($m['uid']) }} from this group?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-secondary" type="submit" title="Remove"><i class="fa-solid fa-user-minus"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">This group has no members.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header fw-semibold">Add member</div>
                <form method="POST" action="{{ route('groups.members.store', $group['cn']) }}" class="card-body">
                    @csrf
                    <label class="form-label" for="uid">Username (uid)</label>
                    <input id="uid" name="uid" value="{{ old('uid') }}" class="form-control mb-3 @error('uid') is-invalid @enderror" required>
                    @error('uid')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                    <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-user-plus me-1"></i>Add</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

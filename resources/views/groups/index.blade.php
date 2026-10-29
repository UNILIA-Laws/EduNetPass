@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 m-0"><i class="fa-solid fa-people-group me-2"></i>LDAP groups</h1>
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search groups">
            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($error)<div class="alert alert-danger">{{ $error }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead><tr><th>Group</th><th>Description</th><th class="text-end">Members</th></tr></thead>
                        <tbody>
                        @forelse($groups as $g)
                            <tr>
                                <td><a href="{{ route('groups.show', $g['cn']) }}" class="fw-semibold text-decoration-none">{{ $g['cn'] }}</a></td>
                                <td class="text-muted">{{ $g['description'] ?: '—' }}</td>
                                <td class="text-end"><span class="badge text-bg-secondary">{{ $g['count'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No groups found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header fw-semibold">Create group</div>
                <form method="POST" action="{{ route('groups.store') }}" class="card-body">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="cn">Name</label>
                        <input id="cn" name="cn" value="{{ old('cn') }}" class="form-control @error('cn') is-invalid @enderror" required>
                        @error('cn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <input id="description" name="description" value="{{ old('description') }}" class="form-control">
                    </div>
                    <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-plus me-1"></i>Create</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

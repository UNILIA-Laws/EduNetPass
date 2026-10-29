@extends('layouts.app')
@section('title', 'eduroam | Groups')

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
            <h1 class="usr-title">Groups</h1>
            <p class="usr-sub">Create LDAP groups and manage who belongs to them.</p>
        </div>
        <span class="count">{{ number_format(count($groups)) }} {{ \Illuminate\Support\Str::plural('group', count($groups)) }}</span>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="panel">

                <form method="GET" action="{{ route('groups.index') }}" class="toolbar">
                    <div class="search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search groups" aria-label="Search groups">
                    </div>
                    <button class="btn-primary-soft">Search</button>
                    @if($q !== '')
                        <a href="{{ route('groups.index') }}" class="btn-clear">Clear</a>
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="tbl">
                        <thead>
                            <tr><th>Group</th><th>Description</th><th class="text-end">Members</th></tr>
                        </thead>
                        <tbody>
                        @forelse($groups as $g)
                            <tr>
                                <td>
                                    <a href="{{ route('groups.show', $g['cn']) }}" class="text-decoration-none" style="font-weight:600;color:var(--accent-ink)">
                                        <i class="fa-solid fa-people-group me-2" style="color:var(--muted)"></i>{{ $g['cn'] }}
                                    </a>
                                </td>
                                <td class="desc">{{ $g['description'] ?: '-' }}</td>
                                <td class="text-end"><span class="chip">{{ $g['count'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty"><i class="fa-solid fa-people-group"></i>No groups found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-head"><span class="ico"><i class="fa-solid fa-plus"></i></span>Create group</div>
                <form method="POST" action="{{ route('groups.store') }}" class="panel-body">
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
                    <button class="btn-primary-soft w-100" type="submit"><i class="fa-solid fa-plus me-1"></i>Create</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
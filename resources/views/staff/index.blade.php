@extends('layouts.app')
@section('title', 'eduroam | Staff')

@section('content')
<div class="container-xl py-4 py-lg-5">

    <div class="mb-3">
        <h1 class="h3 fw-bold mb-1">Staff</h1>
        <p class="text-muted mb-0">Manage who can sign in to this portal.</p>
    </div>

    @include('partials.admin-tabs')

    @if(session('status'))
        <div class="flash ok"><i class="fa-solid fa-circle-check mt-1"></i><span>{{ session('status') }}</span></div>
    @endif
    @if($errors->has('staff'))
        <div class="flash bad"><i class="fa-solid fa-triangle-exclamation mt-1"></i><span>{{ $errors->first('staff') }}</span></div>
    @endif

    <div class="set-card">
        <div class="head">
            <h2>
                <i class="fa-solid fa-user-shield"></i>Staff accounts
                <span class="text-muted fw-normal small">({{ $staff->total() }})</span>
            </h2>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex">
                    <input type="search" name="q" value="{{ $q }}" placeholder="Search name or e-mail"
                           class="form-control form-control-sm" style="min-width:210px">
                </form>
                <a href="{{ route('staff.create') }}" class="btn btn-eduroam btn-sm px-3 d-flex align-items-center">
                    <i class="fa-solid fa-plus me-2"></i>Add staff
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size:.88rem">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Name</th>
                        <th>Role</th>
                        <th>Campus</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($staff as $s)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">
                                {{ $s->name }}
                                @if($s->is(auth()->user()))<span class="text-muted fw-normal">(you)</span>@endif
                            </div>
                            <div class="text-muted small">{{ $s->email }}</div>
                        </td>
                        <td>
                            <span class="pill {{ $s->is_admin ? 'admin' : 'off' }}">
                                {{ $s->is_admin ? 'Administrator' : 'Staff' }}
                            </span>
                        </td>
                        <td>{{ $s->campus?->label ?? 'All campuses' }}</td>
                        <td class="text-end pe-4 text-nowrap">
                            <a href="{{ route('staff.edit', $s) }}" class="icon-btn" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            @unless($s->is(auth()->user()))
                                <form action="{{ route('staff.destroy', $s) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete {{ addslashes($s->name) }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="icon-btn danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No staff found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($staff->hasPages())
            <div class="p-3 border-top">{{ $staff->links() }}</div>
        @endif
    </div>
</div>
@endsection
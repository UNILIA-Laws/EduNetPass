@extends('layouts.app')
@section('title', 'eduroam | Campuses')

@section('content')
<div class="container-xl py-4 py-lg-5">

    <div class="mb-3">
        <h1 class="h3 fw-bold mb-1">Campuses</h1>
        <p class="text-muted mb-0">Manage the campuses users can be assigned to.</p>
    </div>

    @include('partials.admin-tabs')

    @if(session('status'))
        <div class="flash ok"><i class="fa-solid fa-circle-check mt-1"></i><span>{{ session('status') }}</span></div>
    @endif

    <div class="set-card">
        <div class="head">
            <h2>
                <i class="fa-solid fa-building-columns"></i>Campuses
                <span class="text-muted fw-normal small">({{ $campuses->count() }})</span>
            </h2>
            <a href="{{ route('campuses.create') }}" class="btn btn-eduroam btn-sm px-3 d-flex align-items-center">
                <i class="fa-solid fa-plus me-2"></i>Add campus
            </a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size:.88rem">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Label</th>
                        <th>Key</th>
                        <th>DN</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($campuses as $campus)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $campus->label }}</td>
                        <td><code>{{ $campus->key }}</code></td>
                        <td class="text-muted small">{{ $campus->dn }}</td>
                        <td>{{ $campus->sort_order }}</td>
                        <td>
                            <span class="pill {{ $campus->is_active ? 'admin' : 'off' }}">
                                {{ $campus->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $campus->users_count }}</td>
                        <td class="text-end pe-4 text-nowrap">
                            <a href="{{ route('campuses.edit', $campus) }}" class="icon-btn" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form action="{{ route('campuses.destroy', $campus) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete {{ addslashes($campus->label) }}? Its {{ $campus->users_count }} user(s) will be un-assigned.')">
                                @csrf @method('DELETE')
                                <button class="icon-btn danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No campuses yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
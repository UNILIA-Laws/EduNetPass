@extends('layouts.app')

@section('content')
<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Campuses</h1>
        <a href="{{ route('campuses.create') }}">+ Add campus</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Label</th>
                <th>Key</th>
                <th>DN</th>
                <th>Order</th>
                <th>Active</th>
                <th>Users</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($campuses as $campus)
                <tr>
                    <td>{{ $campus->label }}</td>
                    <td><code>{{ $campus->key }}</code></td>
                    <td>{{ $campus->dn }}</td>
                    <td>{{ $campus->sort_order }}</td>
                    <td>{{ $campus->is_active ? 'Yes' : 'No' }}</td>
                    <td>{{ $campus->users_count }}</td>
                    <td>
                        <a href="{{ route('campuses.edit', $campus) }}">Edit</a>
                        <form method="POST" action="{{ route('campuses.destroy', $campus) }}"
                              style="display:inline"
                              onsubmit="return confirm('Delete {{ $campus->label }}? Its {{ $campus->users_count }} user(s) will be un-assigned.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No campuses yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
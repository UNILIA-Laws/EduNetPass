@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <h1 class="h4 mb-3"><i class="fa-solid fa-sitemap me-2"></i>Organizational units</h1>

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
                        <thead><tr><th>OU</th><th>Description</th><th></th></tr></thead>
                        <tbody>
                        @forelse($ous as $o)
                            <tr>
                                <td style="padding-left: {{ 0.75 + $o['depth'] * 1.5 }}rem">
                                    <i class="fa-solid fa-folder{{ $o['depth'] ? '' : '-tree' }} text-muted me-1"></i>
                                    <b>{{ $o['name'] }}</b>
                                    @if($o['campus'])<span class="badge text-bg-info ms-1">campus</span>@endif
                                    <div class="small text-muted text-break">{{ $o['dn'] }}</div>
                                </td>
                                <td class="text-muted">{{ $o['description'] ?: '—' }}</td>
                                <td class="text-end">
                                    @if($o['locked'])
                                        <span class="text-muted" title="In use by the application"><i class="fa-solid fa-lock"></i></span>
                                    @else
                                        <form method="POST" action="{{ route('ous.destroy') }}"
                                              onsubmit="return confirm('Delete OU {{ addslashes($o['name']) }}? It must be empty.')">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="dn" value="{{ $o['dn'] }}">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No organizational units found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header fw-semibold">Create OU</div>
                <form method="POST" action="{{ route('ous.store') }}" class="card-body">
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
                    <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-plus me-1"></i>Create</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

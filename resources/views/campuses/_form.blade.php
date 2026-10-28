@extends('layouts.app')

@php
    $editing = isset($campus) && $campus->exists;
    $title   = $editing ? 'Edit campus' : 'Add campus';
    $action  = $editing ? route('campuses.update', $campus) : route('campuses.store');
@endphp

@section('title', 'eduroam | ' . $title)

@section('content')
<style>
    .campus-page .card-box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden}
    .campus-page .card-head{padding:1rem 1.5rem;border-bottom:1px solid #e5e7eb;background:#f9fafb}
    .campus-page .card-head h2{font-size:1.05rem;font-weight:700;margin:0;display:flex;align-items:center;gap:.6rem}
    .campus-page .card-body-pad{padding:1.5rem}
    .campus-page .alert-box{display:flex;gap:.6rem;padding:.85rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.9rem}
    .campus-page .alert-box.bad{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
    .campus-page .btn-save{background:#0d6efd;color:#fff;border:0;padding:.55rem 1.5rem;border-radius:8px;font-weight:600}
    .campus-page .btn-save:hover{background:#0b5ed7;color:#fff}
</style>

<div class="container-xl py-4 py-lg-5 campus-page">

    <div class="mb-3">
        <h1 class="h3 fw-bold mb-1">{{ $title }}</h1>
        <p class="text-muted mb-0">
            {{ $editing ? 'Update this campus and its directory mapping.' : 'Create a campus that users can be assigned to.' }}
        </p>
    </div>

    @include('partials.admin-tabs')

    @if ($errors->any())
        <div class="alert-box bad">
            <i class="fa-solid fa-triangle-exclamation mt-1"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card-box">
        <div class="card-head">
            <h2><i class="fa-solid fa-building-columns"></i>Campus details</h2>
        </div>

        <form method="POST" action="{{ $action }}" class="card-body-pad">
            @csrf
            @if ($editing) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="key" class="form-label fw-semibold">Key (slug)</label>
                    <input id="key" name="key" type="text"
                           class="form-control @error('key') is-invalid @enderror"
                           value="{{ old('key', $campus->key ?? '') }}"
                           {{ $editing ? 'disabled' : 'required' }}>
                    <div class="form-text">
                        {{ $editing ? 'The key cannot be changed.' : 'Letters, numbers, dashes and underscores. Cannot be changed later.' }}
                    </div>
                    @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="label" class="form-label fw-semibold">Label</label>
                    <input id="label" name="label" type="text" required
                           class="form-control @error('label') is-invalid @enderror"
                           value="{{ old('label', $campus->label ?? '') }}">
                    @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="dn" class="form-label fw-semibold">eduPersonOrgUnitDN</label>
                    <input id="dn" name="dn" type="text" required
                           class="form-control @error('dn') is-invalid @enderror"
                           value="{{ old('dn', $campus->dn ?? '') }}"
                           placeholder="ou=main,dc=example,dc=edu">
                    @error('dn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label for="sort_order" class="form-label fw-semibold">Sort order</label>
                    <input id="sort_order" name="sort_order" type="number" min="0"
                           class="form-control @error('sort_order') is-invalid @enderror"
                           value="{{ old('sort_order', $campus->sort_order ?? 0) }}">
                    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-8 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                               name="is_active" value="1"
                               {{ old('is_active', $campus->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn-save">
                    <i class="fa-solid fa-floppy-disk me-2"></i>{{ $editing ? 'Save changes' : 'Create campus' }}
                </button>
                <a href="{{ route('campuses.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
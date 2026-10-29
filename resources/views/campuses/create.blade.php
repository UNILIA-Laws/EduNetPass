@extends('layouts.app')
@section('title', 'eduroam | Add campus')

@section('content')
@include('partials.usr-style')

<div class="usr">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            @include('users._alerts')

            <div class="usr-head">
                <div>
                    <h1 class="usr-title">Add campus</h1>
                    <p class="usr-sub">Creates a new organizational unit in LDAP. It appears in every campus dropdown straight away.</p>
                </div>
                <a href="{{ route('campuses.index') }}" class="btn-clear"><i class="fa-solid fa-arrow-left me-2"></i>Back to campuses</a>
            </div>

            <div class="panel">
                <div class="panel-head"><span class="ico"><i class="fa-solid fa-building-columns"></i></span>Campus details</div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('campuses.store') }}" autocomplete="off">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="name">Name <span class="hint">(becomes the OU name, saved in lower-case)</span></label>
                                <input id="name" name="name" value="{{ old('name') }}" placeholder="laws"
                                       class="form-control @error('name') is-invalid @enderror" required maxlength="63">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="description">Description <span class="hint">(optional)</span></label>
                                <input id="description" name="description" value="{{ old('description') }}"
                                       class="form-control @error('description') is-invalid @enderror" maxlength="255">
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="note info mx-0 mb-0 mt-3">
                            <i class="fa-solid fa-circle-info mt-1"></i>
                            <span>
                                Will be created as <code id="dnPreview">ou=&hellip;,{{ $container }}</code>.
                                The name can't be changed later, because every user stores this DN.
                            </span>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('campuses.index') }}" class="btn-clear">Cancel</a>
                            <button class="btn-primary-soft px-4"><i class="fa-solid fa-plus me-2"></i>Create campus</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    // Live preview of the DN that will be created
    const nameInput = document.getElementById('name');
    const preview   = document.getElementById('dnPreview');
    const container = @json($container);

    function updatePreview() {
        const n = nameInput.value.trim().toLowerCase() || '…';
        preview.textContent = 'ou=' + n + ',' + container;
    }
    nameInput.addEventListener('input', updatePreview);
    updatePreview();
</script>
@endpush
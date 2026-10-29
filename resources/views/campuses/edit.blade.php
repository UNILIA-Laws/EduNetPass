@extends('layouts.app')
@section('title', 'eduroam | Edit ' . $campus['label'])

@section('content')
@include('partials.usr-style')

<div class="usr">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            @include('users._alerts')

            <div class="usr-head">
                <div class="ou">
                    <span class="ou-ico root" style="width:48px;height:48px;font-size:1.05rem"><i class="fa-solid fa-building-columns"></i></span>
                    <div>
                        <h1 class="usr-title mb-0">{{ $campus['label'] }}</h1>
                        <div class="dn">{{ $campus['dn'] }}</div>
                    </div>
                </div>
                <a href="{{ route('campuses.index') }}" class="btn-clear"><i class="fa-solid fa-arrow-left me-2"></i>Back to campuses</a>
            </div>

            <div class="panel">
                <div class="panel-head"><span class="ico"><i class="fa-solid fa-pen"></i></span>Edit campus</div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('campuses.update', $campus['key']) }}">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Name <span class="hint">(locked)</span></label>
                                <input id="name" value="{{ $campus['key'] }}" class="form-control" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Users in this campus</label>
                                <div>
                                    @if($users === null)
                                        <span class="chip">unknown</span>
                                    @else
                                        <a href="{{ route('users.index', ['campus' => $campus['key']]) }}" class="chip campus text-decoration-none">
                                            {{ number_format($users) }} user{{ $users === 1 ? '' : 's' }}
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="description">Description <span class="hint">(optional)</span></label>
                                <input id="description" name="description" value="{{ old('description', $campus['description']) }}"
                                       class="form-control @error('description') is-invalid @enderror" maxlength="255">
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="note info mx-0 mb-0 mt-3">
                            <i class="fa-solid fa-circle-info mt-1"></i>
                            <span>The name can't be changed: every user in this campus stores its DN. To rename a campus, create a new one, move the users to it (Edit user, then Campus), and delete the old one.</span>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('campuses.index') }}" class="btn-clear">Cancel</a>
                            <button class="btn-primary-soft px-4"><i class="fa-solid fa-floppy-disk me-2"></i>Save changes</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</div>
@endsection
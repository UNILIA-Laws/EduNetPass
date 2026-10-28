@extends('layouts.app')
@section('title', 'eduroam | Add staff')

@section('content')
<div class="container-xl py-4 py-lg-5" style="max-width:820px">
    <a href="{{ route('staff.index') }}" class="text-decoration-none small text-muted">
        <i class="fa-solid fa-arrow-left me-1"></i>Back to staff
    </a>
    <h1 class="h3 fw-bold mt-2 mb-4">Add staff member</h1>

    @include('partials.admin-tabs')

    <div class="set-card">
        <div class="head"><h2><i class="fa-solid fa-user-plus"></i>New account</h2></div>
        <div class="body">
            <form action="{{ route('staff.store') }}" method="POST" autocomplete="off">
                @csrf
                @include('staff._form')
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('staff.index') }}" class="btn btn-light">Cancel</a>
                    <button class="btn btn-eduroam px-4"><i class="fa-solid fa-check me-2"></i>Create account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
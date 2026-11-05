@extends('layouts.app')
@section('title', 'MyLDAP | Add staff')

@section('content')
@include('partials.usr-style')

<div class="usr" style="padding:1.25rem 0 1.5rem">
<div class="container" style="max-width:820px">

    @include('users._alerts')

    <div class="usr-head" style="margin-bottom:.8rem">
        <div>
            <h1 class="usr-title" style="font-size:1.35rem">Add staff member</h1>
            <p class="usr-sub">Create a new account that can sign in to MyLDAP.</p>
        </div>
        <a href="{{ route('staff.index') }}" class="btn-clear"><i class="fa-solid fa-arrow-left me-2"></i>Back to staff</a>
    </div>

    <div class="panel">
        <div class="panel-head" style="padding:.75rem 1.1rem">
            <span class="ico"><i class="fa-solid fa-user-plus"></i></span>New account
        </div>
        <form action="{{ route('staff.store') }}" method="POST" autocomplete="off">
            @csrf
            <div class="panel-body">
                @include('staff._form')
            </div>
            <div class="d-flex justify-content-end gap-2 px-3 py-2" style="border-top:1px solid var(--line);background:#FAFBFD">
                <a href="{{ route('staff.index') }}" class="btn-clear">Cancel</a>
                <button class="btn-primary-soft px-4"><i class="fa-solid fa-check me-2"></i>Create account</button>
            </div>
        </form>
    </div>

</div>
</div>
@endsection
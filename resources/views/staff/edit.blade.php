@extends('layouts.app')
@section('title', 'MyLDAP | Edit staff')

@section('content')
@include('partials.usr-style')

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp

<div class="usr" style="padding:1.25rem 0 1.5rem">
<div class="container" style="max-width:820px">

    @include('users._alerts')

    <div class="usr-head" style="margin-bottom:.8rem">
        <div class="person">
            <span class="avatar">{{ $initials ?: '?' }}</span>
            <div>
                <h1 class="usr-title mb-0" style="font-size:1.3rem">Edit {{ $user->name }}</h1>
                <p class="usr-sub">{{ $user->email }}</p>
            </div>
            @if($user->is_admin)
                <span class="chip campus ms-2"><i class="fa-solid fa-shield-halved me-1"></i>Admin</span>
            @endif
        </div>
        <a href="{{ route('staff.index') }}" class="btn-clear"><i class="fa-solid fa-arrow-left me-2"></i>Back to staff</a>
    </div>


    <div class="panel">
        <div class="panel-head" style="padding:.75rem 1.1rem">
            <span class="ico"><i class="fa-solid fa-user-pen"></i></span>Account details
        </div>
        <form action="{{ route('staff.update', $user) }}" method="POST" autocomplete="off">
            @csrf @method('PUT')
            <div class="panel-body">
                @include('staff._form')
            </div>
            <div class="d-flex justify-content-end gap-2 px-3 py-2" style="border-top:1px solid var(--line);background:#FAFBFD">
                <a href="{{ route('staff.index') }}" class="btn-clear">Cancel</a>
                <button class="btn-primary-soft px-4"><i class="fa-solid fa-check me-2"></i>Save changes</button>
            </div>
        </form>
    </div>

</div>
</div>
@endsection
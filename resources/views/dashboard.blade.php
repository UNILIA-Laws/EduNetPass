@extends('layouts.app')
@section('title', 'eduroam | Dashboard')

@section('content')
<div class="container py-5">
    <h1 class="h3 fw-bold mb-1">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-muted mb-4">What would you like to do?</p>

    <div class="row g-4">
        <div class="col-md-6 col-xl-4">
            <a href="{{ route('enroll') }}" class="card h-100 text-decoration-none shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="fs-2 text-primary mb-3"><i class="fa-solid fa-user-plus"></i></div>
                    <h2 class="h5 text-dark">Add students</h2>
                    <p class="text-muted small mb-0">Create eduroam accounts one at a time or from a CSV/Excel file, and e-mail the login details.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-4">
            <a href="{{ route('users.index') }}" class="card h-100 text-decoration-none shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="fs-2 text-primary mb-3"><i class="fa-solid fa-users"></i></div>
                    <h2 class="h5 text-dark">Manage users</h2>
                    <p class="text-muted small mb-0">Search students in LDAP, edit their details, reset passwords or delete accounts.</p>
                </div>
            </a>
        </div>

        @if(auth()->user()->is_admin && Route::has('settings.edit'))
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('settings.edit') }}" class="card h-100 text-decoration-none shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="fs-2 text-primary mb-3"><i class="fa-solid fa-gear"></i></div>
                        <h2 class="h5 text-dark">Settings</h2>
                        <p class="text-muted small mb-0">Campuses, entitlements, notification e-mail and upload limits.</p>
                    </div>
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
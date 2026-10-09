<nav class="navbar navbar-custom sticky-top">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <img src="{{ asset('unilia.jpg') }}" alt="UNILIA Logo" class="logo-img me-3">
            <a href="{{ url('/') }}" class="navbar-brand-custom">
                <i class="fa-solid fa-wifi me-2"></i>EduNetPass
            </a>
        </div>

        @auth
            <div class="d-flex align-items-center flex-wrap gap-2 gap-md-3">
                <a href="{{ route('dashboard') }}"
                   class="btn btn-sm {{ request()->routeIs('dashboard') ? 'btn-eduroam' : 'btn-outline-secondary' }} rounded-pill px-3">
                    <i class="fa-solid fa-gauge me-1"></i>Dashboard
                </a>
                <a href="{{ route('enroll') }}"
                   class="btn btn-sm {{ request()->routeIs('enroll') ? 'btn-eduroam' : 'btn-outline-secondary' }} rounded-pill px-3">
                    <i class="fa-solid fa-user-plus me-1"></i>Add students
                </a>
                <a href="{{ route('users.index') }}"
                   class="btn btn-sm {{ request()->routeIs('users.*') && !request()->routeIs('users.store') ? 'btn-eduroam' : 'btn-outline-secondary' }} rounded-pill px-3">
                    <i class="fa-solid fa-users me-1"></i>Users
                </a>

                @if(auth()->user()->is_admin && Route::has('settings.edit'))
                    <a href="{{ route('settings.edit') }}"
                       class="btn btn-sm {{ request()->routeIs('settings.*') ? 'btn-eduroam' : 'btn-outline-secondary' }} rounded-pill px-3">
                        <i class="fa-solid fa-gear me-1"></i>Settings
                    </a>
                @endif

                <small class="text-muted d-none d-md-block">
                    <i class="fa-solid fa-user-shield me-1"></i>{{ auth()->user()->email }}
                </small>

                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa-solid fa-right-from-bracket me-1"></i>Log out
                    </button>
                </form>
            </div>
        @endauth
    </div>
</nav>
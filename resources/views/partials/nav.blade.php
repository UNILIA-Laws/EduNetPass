@php
    $u = auth()->user();
    $initials = $u
        ? collect(preg_split('/\s+/', trim($u->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('')
        : '';
    $links = [
        ['route' => 'dashboard',   'active' => request()->routeIs('dashboard'),                                     'icon' => 'fa-gauge-high', 'label' => 'Dashboard'],
        ['route' => 'enroll',      'active' => request()->routeIs('enroll'),                                        'icon' => 'fa-user-plus',  'label' => 'Add students'],
        ['route' => 'users.index', 'active' => request()->routeIs('users.*') && !request()->routeIs('users.store'), 'icon' => 'fa-users',      'label' => 'Users'],
    ];
@endphp

<header class="topbar">
    <div class="container-xl">
        <nav class="navbar navbar-expand-lg p-0">

            <a href="{{ url('/') }}" class="brand">
                <img src="{{ asset('unilia.jpg') }}" alt="UNILIA Logo">
                <span class="brand-name">EduNetPass</span>
            </a>

            @auth
                <button class="navbar-toggler toggler ms-auto" type="button" data-bs-toggle="collapse"
                        data-bs-target="#topbarMenu" aria-controls="topbarMenu" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="collapse navbar-collapse" id="topbarMenu">
                    <ul class="menu mx-lg-auto">
                        @foreach($links as $l)
                            <li>
                                <a href="{{ route($l['route']) }}" class="{{ $l['active'] ? 'active' : '' }}"
                                   @if($l['active']) aria-current="page" @endif>
                                    <i class="fa-solid {{ $l['icon'] }}"></i>{{ $l['label'] }}
                                </a>
                            </li>
                        @endforeach

                        @if($u->is_admin && Route::has('settings.edit'))
                            <li>
                                <a href="{{ route('settings.edit') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-sliders"></i>Settings
                                </a>
                            </li>
                        @endif
                    </ul>

                    <div class="nav-end dropdown">
                        <button class="user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar">{{ $initials }}</span>
                            <span class="user-meta d-none d-xl-block">
                                <b>{{ $u->name }}</b>
                                <small>{{ $u->is_admin ? 'Administrator' : 'Staff' }}</small>
                            </span>
                            <i class="fa-solid fa-chevron-down chev"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-header-info">
                                <b>{{ $u->name }}</b>
                                <span>{{ $u->email }}</span>
                                @if($u->is_admin)
                                    <div class="role-tag"><i class="fa-solid fa-shield-halved me-1"></i>Admin</div>
                                @endif
                            </li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item danger">
                                        <i class="fa-solid fa-right-from-bracket"></i>Log out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            @endauth

        </nav>
    </div>
</header>
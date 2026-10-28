@php
    $u = auth()->user();
    $initials = $u
        ? collect(preg_split('/\s+/', trim($u->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('')
        : '';

    // Items with a 'route' are live. Items with only 'href' => '#' are placeholders to wire up later.
    // Entries with a 'heading' render as section labels.
    $links = [
        ['route' => 'dashboard', 'active' => request()->routeIs('dashboard'), 'icon' => 'fa-gauge-high', 'label' => 'Dashboard'],

        ['heading' => 'Directory'],
        ['route' => 'enroll',      'active' => request()->routeIs('enroll'),                                        'icon' => 'fa-user-plus',     'label' => 'Add LDAP users'],
        ['route' => 'users.index', 'active' => request()->routeIs('users.*') && !request()->routeIs('users.store'), 'icon' => 'fa-address-book', 'label' => 'View LDAP users'],
        ['href' => '#',            'active' => false,                                                               'icon' => 'fa-people-group',  'label' => 'LDAP groups'],
        ['href' => '#',            'active' => false,                                                               'icon' => 'fa-sitemap',       'label' => 'Organizational units'],
        ['href' => '#',            'active' => false,                                                               'icon' => 'fa-file-import',   'label' => 'Bulk import'],
        ['href' => '#',            'active' => false,                                                               'icon' => 'fa-key',           'label' => 'Password resets'],
        ['route' => 'campuses.index', 'active' => request()->routeIs('campuses.*'),                                 'icon' => 'fa-building-columns', 'label' => 'Campuses'],

        ['heading' => 'Administration'],
        ['route' => 'staff.index', 'active' => request()->routeIs('staff.*'),                                       'icon' => 'fa-user-gear',     'label' => 'System users'],
        ['href' => '#',            'active' => false,                                                               'icon' => 'fa-clipboard-list','label' => 'Audit logs'],
        ['href' => '#',            'active' => false,                                                               'icon' => 'fa-server',        'label' => 'LDAP server settings'],
    ];
@endphp

@guest
    {{-- Guests (e.g. login page): simple brand strip, no sidebar --}}
    <header class="app-topbar">
        <a href="{{ url('/') }}" class="brand d-flex align-items-center gap-2 text-decoration-none">
            <img src="{{ asset('unilia.jpg') }}" alt="UNILIA Logo" style="height:36px;border-radius:8px;">
            <span class="brand-name">EduNetPass</span>
        </a>
    </header>
@endguest

@auth
    {{-- Top bar: hamburger + brand on mobile, user menu always on the top right --}}
    <header class="app-topbar">
        <button class="toggler d-lg-none" type="button" id="sidebarToggle" aria-controls="sidebar" aria-label="Open menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <span class="brand-name d-lg-none">EduNetPass</span>

        <div class="nav-end dropdown ms-auto">
            <button class="user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar">{{ $initials }}</span>
                <span class="user-meta d-none d-md-block">
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
    </header>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
        <a href="{{ url('/') }}" class="brand">
            <img src="{{ asset('unilia.jpg') }}" alt="UNILIA Logo">
            <span class="brand-name">EduNetPass</span>
        </a>

        <ul class="menu">
            @foreach($links as $l)
                @if(isset($l['heading']))
                    <li class="menu-label">{{ $l['heading'] }}</li>
                @else
                    <li>
                        <a href="{{ isset($l['route']) ? route($l['route']) : $l['href'] }}"
                           class="{{ $l['active'] ? 'active' : '' }}"
                           @if($l['active']) aria-current="page" @endif>
                            <i class="fa-solid {{ $l['icon'] }}"></i>{{ $l['label'] }}
                        </a>
                    </li>
                @endif
            @endforeach

            @if($u->is_admin && Route::has('settings.edit'))
                <li>
                    <a href="{{ route('settings.edit') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-sliders"></i>Settings
                    </a>
                </li>
            @endif
        </ul>
    </aside>

    @push('scripts')
    <script>
        (function () {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const toggle = document.getElementById('sidebarToggle');
            const set = (open) => {
                sidebar.classList.toggle('open', open);
                backdrop.classList.toggle('show', open);
            };
            toggle.addEventListener('click', () => set(!sidebar.classList.contains('open')));
            backdrop.addEventListener('click', () => set(false));
            sidebar.querySelectorAll('.menu a').forEach(a => a.addEventListener('click', () => set(false)));
        })();
    </script>
    @endpush
@endauth
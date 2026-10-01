<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'eduroam | Provisioning')</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('unilia.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --eduroam-blue: #003366; --eduroam-accent: #f39200; }
        body { background-color: #f4f7f9; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; min-height: 100vh; }
        .navbar-custom { background-color: white; border-bottom: 2px solid #e9ecef; padding: 0.75rem 2rem; }
        .navbar-brand-custom { font-weight: 800; color: var(--eduroam-blue); letter-spacing: -1px; text-decoration: none; font-size: 1.5rem; }
        .logo-img { height: 45px; width: auto; border-radius: 4px; }
        .card { border: none; border-radius: 16px; overflow: hidden; }
        .card-header { background-color: var(--eduroam-blue) !important; color: white; padding: 1.2rem; border: none; }
        .btn-eduroam { background-color: var(--eduroam-blue); color: white; border-radius: 8px; padding: 12px; transition: 0.3s; border: none; font-weight: 600; }
        .btn-eduroam:hover { background-color: #002244; color: white; box-shadow: 0 4px 12px rgba(0,51,102,0.2); }
        .csv-helper-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e9ecef; }
        .code-block { background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #dee2e6; font-family: 'Courier New', monospace; color: var(--eduroam-blue); font-weight: bold; display: block; margin-top: 10px; font-size: .85rem; }
        .upload-zone { border: 2px dashed #dee2e6; border-radius: 12px; padding: 40px 20px; transition: 0.3s; background: #fafafa; cursor: pointer; }
        .upload-zone:hover, .upload-zone.drag { border-color: var(--eduroam-blue); background: #f0f5ff; }
        .nav-pills .nav-link { color: var(--eduroam-blue); font-weight: 600; }
        .nav-pills .nav-link.active { background-color: var(--eduroam-blue); }
        .spinner-border-sm { width: 1rem; height: 1rem; border-width: 0.2em; }
    </style>
</head>
<body>
<nav class="navbar navbar-custom sticky-top">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <img src="{{ asset('unilia.jpg') }}" alt="UNILIA Logo" class="logo-img me-3">
            <a href="{{ url('/') }}" class="navbar-brand-custom"><i class="fa-solid fa-wifi me-2"></i>EduNetPass</a>
        </div>
        @auth
            <div class="d-flex align-items-center gap-2 gap-md-3">
                <a href="{{ route('home') }}" class="btn btn-sm {{ request()->routeIs('home') ? 'btn-eduroam' : 'btn-outline-secondary' }} rounded-pill px-3"><i class="fa-solid fa-user-plus me-1"></i>Add students</a>
                <a href="{{ route('users.index') }}" class="btn btn-sm {{ request()->routeIs('users.index', 'users.edit') ? 'btn-eduroam' : 'btn-outline-secondary' }} rounded-pill px-3"><i class="fa-solid fa-users me-1"></i>Users</a>
                <small class="text-muted d-none d-md-block"><i class="fa-solid fa-user-shield me-1"></i>{{ auth()->user()->email }}</small>
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="fa-solid fa-right-from-bracket me-1"></i>Log out</button>
                </form>
            </div>
        @endauth
    </div>
</nav>

@yield('content')

<p class="text-center text-muted my-4" style="font-size: 0.75rem;">UNILIA Eduroam Portal &copy; {{ date('Y') }}. All rights reserved.</p>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>

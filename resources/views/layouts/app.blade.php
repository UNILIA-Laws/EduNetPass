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
        :root {
            --eduroam-blue: #003366;
            --eduroam-accent: #f39200;
            --sidebar-w: 260px;
        }
        body { background-color: #f4f7f9; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; min-height: 100vh; }

        /* ---------- Left sidebar (partials/nav.blade.php) ---------- */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar-w); z-index: 1040;
            display: flex; flex-direction: column;
            background: #fff; border-right: 1px solid #E3E8F0;
            padding: 1rem .85rem;
            transform: translateX(-100%); transition: transform .25s ease;
            overflow-y: auto;
        }
        .sidebar.open { transform: translateX(0); box-shadow: 0 0 40px rgba(23,32,51,.25); }
        .sidebar-backdrop {
            position: fixed; inset: 0; z-index: 1035; background: rgba(23,32,51,.45); display: none;
        }
        .sidebar-backdrop.show { display: block; }

        .sidebar .brand { display: flex; align-items: center; gap: .7rem; text-decoration: none; padding: .25rem .5rem 1.1rem; }
        .sidebar .brand img { height: 40px; width: auto; max-width: 100px; border-radius: 8px; }
        .brand-name {
            font-size: 1.2rem; font-weight: 800; letter-spacing: -.03em; color: var(--eduroam-blue);
        }

        .sidebar .menu { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .2rem; }
        .sidebar .menu-label {
            font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
            color: #98A2B3; padding: 1rem .9rem .3rem;
        }
        .sidebar .menu a {
            display: flex; align-items: center; gap: .7rem; padding: .65rem .9rem; border-radius: 10px;
            font-size: .9rem; font-weight: 600; color: #667085; text-decoration: none;
            transition: background .15s, color .15s;
        }
        .sidebar .menu a i { width: 18px; text-align: center; font-size: .92rem; opacity: .85; }
        .sidebar .menu a:hover { background: #F0F3F8; color: #172033; }
        .sidebar .menu a.active { background: #E8EEFC; color: #1B44AB; }
        .sidebar .menu a.active i { opacity: 1; }

        /* ---------- Top bar (user menu stays top right) ---------- */
        .app-topbar {
            position: sticky; top: 0; z-index: 1030; display: flex; align-items: center; gap: .75rem;
            background: rgba(255,255,255,.85);
            backdrop-filter: saturate(180%) blur(12px);
            -webkit-backdrop-filter: saturate(180%) blur(12px);
            border-bottom: 1px solid #E3E8F0; padding: .6rem 1rem;
            min-height: 60px;
        }
        .app-topbar .toggler {
            border: 1px solid #E3E8F0; border-radius: 10px; width: 40px; height: 40px; background: #fff; color: #172033;
        }

        .app-topbar .user-btn {
            display: flex; align-items: center; gap: .6rem; background: transparent; border: 1px solid #E3E8F0;
            border-radius: 999px; padding: .25rem .75rem .25rem .25rem; transition: background .15s, border-color .15s;
        }
        .app-topbar .user-btn:hover, .app-topbar .user-btn.show { background: #F7F9FC; border-color: #CBD3E1; }
        .app-topbar .avatar {
            width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center;
            font-size: .75rem; font-weight: 700; color: #fff; background: linear-gradient(135deg, #003366, #1F6FC7);
        }
        .app-topbar .user-meta { line-height: 1.15; text-align: left; }
        .app-topbar .user-meta b { display: block; font-size: .8rem; color: #172033; font-weight: 650; }
        .app-topbar .user-meta small { font-size: .7rem; color: #667085; }
        .app-topbar .user-btn .chev { font-size: .65rem; color: #98A2B3; }

        .app-topbar .dropdown-menu {
            border: 1px solid #E3E8F0; border-radius: 14px; padding: .4rem; min-width: 230px;
            box-shadow: 0 16px 32px -12px rgba(23,32,51,.25); margin-top: .6rem !important;
        }
        .app-topbar .dropdown-header-info { padding: .6rem .75rem .7rem; border-bottom: 1px solid #EEF1F6; margin-bottom: .35rem; }
        .app-topbar .dropdown-header-info b { display: block; font-size: .88rem; color: #172033; }
        .app-topbar .dropdown-header-info span { font-size: .76rem; color: #667085; word-break: break-all; }
        .app-topbar .role-tag {
            display: inline-block; margin-top: .4rem; font-size: .68rem; font-weight: 700; padding: .12rem .5rem;
            border-radius: 999px; background: #EFE9FC; color: #6D42D3;
        }
        .app-topbar .dropdown-item { border-radius: 8px; font-size: .86rem; padding: .5rem .75rem; display: flex; align-items: center; gap: .6rem; }
        .app-topbar .dropdown-item.danger { color: #C93A3A; }
        .app-topbar .dropdown-item.danger:hover { background: #FCE9E9; color: #C93A3A; }

        .main-content { min-height: calc(100vh - 60px); display: flex; flex-direction: column; }
        .main-content > .page { flex: 1; }

        @media (min-width: 992px) {
            .sidebar { transform: none; box-shadow: none !important; z-index: 1030; }
            .sidebar-backdrop { display: none !important; }
            .app-topbar { padding-left: 1.5rem; padding-right: 1.5rem; }
            body.has-sidebar .app-topbar,
            body.has-sidebar .main-content { margin-left: var(--sidebar-w); }
        }

        /* ---------- Shared components ---------- */
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
        a.card { transition: transform .15s, box-shadow .15s; }
        a.card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; }
    </style>
    @stack('styles')
</head>
<body class="{{ auth()->check() ? 'has-sidebar' : '' }}">

{{-- Renders the top bar (user menu top right), sidebar and backdrop --}}
@include('partials.nav')

<div class="main-content">
    <div class="page">
        @yield('content')
    </div>

    <p class="text-center text-muted my-4" style="font-size: 0.75rem;">UNILIA Eduroam Portal &copy; {{ date('Y') }}. All rights reserved.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
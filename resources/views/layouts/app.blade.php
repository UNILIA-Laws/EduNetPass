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

        /* ---------- Top bar (partials/nav.blade.php) ---------- */
        .topbar {
            position: sticky; top: 0; z-index: 1030;
            background: rgba(255,255,255,.85);
            backdrop-filter: saturate(180%) blur(12px);
            -webkit-backdrop-filter: saturate(180%) blur(12px);
            border-bottom: 1px solid #E3E8F0;
            padding: .6rem 0;
        }
        .topbar .brand { display: flex; align-items: center; gap: .7rem; text-decoration: none; }
        .topbar .brand img { height: 40px; width: auto; max-width: 120px; border-radius: 8px; }
        .topbar .brand-name {
            font-size: 1.25rem; font-weight: 800; letter-spacing: -.03em; color: var(--eduroam-blue);
            display: flex; align-items: center; gap: .45rem;
        }
        .topbar .brand-name i {
            width: 30px; height: 30px; display: grid; place-items: center; font-size: .85rem; color: #fff;
            border-radius: 9px; background: linear-gradient(135deg, #003366, #1F6FC7);
        }

        .topbar .menu { display: flex; align-items: center; gap: .25rem; list-style: none; margin: 0; padding: 0; }
        .topbar .menu a {
            display: inline-flex; align-items: center; gap: .5rem; padding: .5rem .9rem; border-radius: 10px;
            font-size: .88rem; font-weight: 600; color: #667085; text-decoration: none;
            transition: background .15s, color .15s;
        }
        .topbar .menu a i { font-size: .9rem; opacity: .85; }
        .topbar .menu a:hover { background: #F0F3F8; color: #172033; }
        .topbar .menu a.active { background: #E8EEFC; color: #1B44AB; }
        .topbar .menu a.active i { opacity: 1; }

        .topbar .user-btn {
            display: flex; align-items: center; gap: .6rem; background: transparent; border: 1px solid #E3E8F0;
            border-radius: 999px; padding: .25rem .75rem .25rem .25rem; transition: background .15s, border-color .15s;
        }
        .topbar .user-btn:hover, .topbar .user-btn.show { background: #F7F9FC; border-color: #CBD3E1; }
        .topbar .avatar {
            width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center;
            font-size: .75rem; font-weight: 700; color: #fff; background: linear-gradient(135deg, #003366, #1F6FC7);
        }
        .topbar .user-meta { line-height: 1.15; text-align: left; }
        .topbar .user-meta b { display: block; font-size: .8rem; color: #172033; font-weight: 650; }
        .topbar .user-meta small { font-size: .7rem; color: #667085; }
        .topbar .user-btn .chev { font-size: .65rem; color: #98A2B3; }

        .topbar .dropdown-menu {
            border: 1px solid #E3E8F0; border-radius: 14px; padding: .4rem; min-width: 230px;
            box-shadow: 0 16px 32px -12px rgba(23,32,51,.25); margin-top: .6rem !important;
        }
        .topbar .dropdown-header-info { padding: .6rem .75rem .7rem; border-bottom: 1px solid #EEF1F6; margin-bottom: .35rem; }
        .topbar .dropdown-header-info b { display: block; font-size: .88rem; color: #172033; }
        .topbar .dropdown-header-info span { font-size: .76rem; color: #667085; word-break: break-all; }
        .topbar .role-tag {
            display: inline-block; margin-top: .4rem; font-size: .68rem; font-weight: 700; padding: .12rem .5rem;
            border-radius: 999px; background: #EFE9FC; color: #6D42D3;
        }
        .topbar .dropdown-item { border-radius: 8px; font-size: .86rem; padding: .5rem .75rem; display: flex; align-items: center; gap: .6rem; }
        .topbar .dropdown-item.danger { color: #C93A3A; }
        .topbar .dropdown-item.danger:hover { background: #FCE9E9; color: #C93A3A; }

        .topbar .toggler {
            border: 1px solid #E3E8F0; border-radius: 10px; width: 40px; height: 40px; background: #fff; color: #172033;
        }

        @media (max-width: 991.98px) {
            .topbar .menu { flex-direction: column; align-items: stretch; margin: .9rem 0 .5rem; gap: .2rem; }
            .topbar .menu a { padding: .7rem .9rem; }
            .topbar .nav-end { padding-top: .75rem; border-top: 1px solid #EEF1F6; }
            .topbar .user-btn { width: 100%; }
            .topbar .user-btn .chev { margin-left: auto; }
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
<body>

@include('partials.nav')

@yield('content')

<p class="text-center text-muted my-4" style="font-size: 0.75rem;">UNILIA Eduroam Portal &copy; {{ date('Y') }}. All rights reserved.</p>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
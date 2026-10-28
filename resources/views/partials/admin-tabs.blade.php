@push('styles')
<style>
    .set-tabs { display:inline-flex; background:#EEF1F5; border-radius:12px; padding:4px; gap:2px; margin-bottom:1.5rem; }
    .set-tabs a { padding:.45rem 1rem; border-radius:9px; font-size:.86rem; font-weight:600; color:#667085; text-decoration:none; transition:.15s; }
    .set-tabs a:hover { color:#172033; }
    .set-tabs a.active { background:#fff; color:#172033; box-shadow:0 1px 2px rgba(23,32,51,.12); }

    .set-card { background:#fff; border:1px solid #E3E8F0; border-radius:16px; box-shadow:0 1px 2px rgba(23,32,51,.04); }
    .set-card .head { padding:1.1rem 1.25rem; border-bottom:1px solid #EEF1F6; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
    .set-card .head h2 { font-size:1rem; font-weight:700; margin:0; color:#172033; }
    .set-card .head h2 i { color:#2557D6; margin-right:.45rem; }
    .set-card .body { padding:1.25rem; }
    .pill { display:inline-flex; align-items:center; gap:.35rem; padding:.15rem .6rem; border-radius:999px; font-size:.74rem; font-weight:600; }
    .pill::before { content:""; width:6px; height:6px; border-radius:50%; background:currentColor; }
    .pill.ok { background:#E3F5EC; color:#178A5C; }
    .pill.off { background:#EEF1F5; color:#667085; }
    .pill.admin { background:#EFE9FC; color:#6D42D3; }
    .icon-btn { width:34px; height:34px; border-radius:9px; border:1px solid #E3E8F0; background:#fff; color:#667085; display:inline-grid; place-items:center; transition:.15s; text-decoration:none; }
    .icon-btn:hover { background:#F0F3F8; color:#172033; }
    .icon-btn.danger:hover { background:#FCE9E9; color:#C93A3A; border-color:#F3C5C5; }
    .flash { display:flex; gap:.6rem; border-radius:12px; padding:.75rem 1rem; font-size:.88rem; margin-bottom:1rem; }
    .flash.ok { background:#E3F5EC; color:#0F6B47; }
    .flash.bad { background:#FCE9E9; color:#8E2323; }
</style>
@endpush

<div class="set-tabs">
    <a href="{{ route('campuses.index') }}" class="{{ request()->routeIs('campuses.*') ? 'active' : '' }}">
        <i class="fa-solid fa-building-columns me-1"></i>Campuses
    </a>
    <a href="{{ route('staff.index') }}" class="{{ request()->routeIs('staff.*') ? 'active' : '' }}">
        <i class="fa-solid fa-user-shield me-1"></i>Staff
    </a>
</div>
{{--
    Shared design for the eduroam admin pages.
    Place at: resources/views/partials/usr-style.blade.php
    Use with: @include('partials.usr-style')  (inside @section('content'), before the markup)
    Wrap page markup in: <div class="usr"><div class="container"> ... </div></div>
    Modals must carry the class "usr-modal" (they render outside .usr).
--}}
<style>
    .usr, .usr-modal {
        --ink: #172033;
        --muted: #667085;
        --line: #E3E8F0;
        --surface: #FFFFFF;
        --canvas: #F3F5F9;
        --accent: #2557D6;
        --accent-ink: #1B44AB;
        --accent-soft: #E8EEFC;
        --warn: #A8620B;  --warn-soft: #FDF1DF;
        --bad: #C93A3A;   --bad-soft: #FCE9E9;
        --good: #1F7A4D;  --good-soft: #E3F5EC;
        --violet: #6A3FC4; --violet-soft: #EFE9FB;
        --idle-soft: #EEF1F5;
        color: var(--ink);
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    .usr {
        background: var(--canvas);
        padding: 2rem 0 3rem;
    }
    .usr :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

    /* Heading */
    .usr-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
    .usr-title { font-size: 1.6rem; font-weight: 700; letter-spacing: -.02em; margin: 0 0 .2rem; }
    .usr-sub { color: var(--muted); font-size: .92rem; margin: 0; }
    .count {
        font-size: .82rem; font-weight: 600; color: var(--accent-ink); background: var(--accent-soft);
        padding: .3rem .8rem; border-radius: 999px;
    }

    /* Panel */
    .panel {
        background: var(--surface); border: 1px solid var(--line); border-radius: 16px;
        box-shadow: 0 1px 2px rgba(23,32,51,.04), 0 8px 24px -12px rgba(23,32,51,.12);
        overflow: hidden;
    }
    .panel-head {
        display: flex; align-items: center; gap: .6rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--line);
        font-weight: 700; font-size: .95rem;
    }
    .panel-head .ico {
        width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center;
        background: var(--accent-soft); color: var(--accent-ink); font-size: .75rem;
    }
    .panel-body { padding: 1.1rem; }

    /* Toolbar */
    .toolbar { display: flex; flex-wrap: wrap; gap: .6rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--line); }
    .toolbar .search { position: relative; flex: 1 1 260px; }
    .toolbar .search i { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); color: #98A2B3; font-size: .85rem; pointer-events: none; }
    .toolbar .search .form-control { padding-left: 2.2rem; }
    .toolbar .form-select { flex: 0 1 200px; }

    /* Form controls */
    .usr .form-control, .usr .form-select, .usr-modal .form-control {
        font-size: .88rem; border: 1px solid #D5DBE6; border-radius: 8px; padding: .5rem .75rem; background-color: #fff; color: var(--ink);
        transition: border-color .15s, box-shadow .15s;
    }
    .usr .form-control::placeholder, .usr-modal .form-control::placeholder { color: #A5AEBD; }
    .usr .form-control:focus, .usr .form-select:focus, .usr-modal .form-control:focus {
        border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,87,214,.15);
    }
    .usr .form-control.is-invalid, .usr .form-select.is-invalid { border-color: var(--bad); }
    .usr .form-label, .usr-modal .form-label { font-size: .8rem; font-weight: 600; margin-bottom: .3rem; }
    .usr .form-label .hint, .usr-modal .form-label .hint { color: var(--muted); font-weight: 400; }
    .usr .invalid-feedback { font-size: .78rem; color: var(--bad); }

    /* Buttons */
    .btn-primary-soft {
        background: var(--accent); color: #fff; border: 0; border-radius: 8px; font-size: .86rem; font-weight: 600; padding: .5rem 1rem;
        transition: background .15s;
    }
    .btn-primary-soft:hover { background: var(--accent-ink); color: #fff; }
    .btn-danger-soft {
        background: var(--bad); color: #fff; border: 0; border-radius: 8px; font-size: .86rem; font-weight: 600; padding: .5rem 1rem;
        transition: background .15s;
    }
    .btn-danger-soft:hover { background: #A82E2E; color: #fff; }
    .btn-clear {
        font-size: .86rem; font-weight: 600; color: var(--muted); background: transparent; border: 1px solid #D5DBE6;
        border-radius: 8px; padding: .5rem .9rem; text-decoration: none; display: inline-flex; align-items: center;
    }
    .btn-clear:hover { background: var(--idle-soft); color: var(--ink); }

    /* Notices */
    .note {
        display: flex; gap: .55rem; align-items: flex-start; border-radius: 10px; padding: .6rem .85rem; font-size: .84rem; margin-bottom: 1rem;
    }
    .note ul { margin: 0; padding-left: 1.1rem; }
    .note.bad  { background: var(--bad-soft); color: #8E2323; }
    .note.good { background: var(--good-soft); color: var(--good); }
    .note.warn { background: var(--warn-soft); color: var(--warn); }
    .note.info { background: var(--accent-soft); color: var(--accent-ink); margin: 1rem 1.1rem 0; }

    /* Table */
    .tbl { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .86rem; margin: 0; }
    .tbl th {
        background: #F7F9FC; text-align: left; font-weight: 600; font-size: .78rem; color: var(--muted);
        padding: .65rem .9rem; border-bottom: 1px solid var(--line); white-space: nowrap;
    }
    .tbl td { padding: .75rem .9rem; border-bottom: 1px solid #EEF1F6; vertical-align: middle; }
    .tbl td.mail { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tbl tbody tr:last-child td { border-bottom: 0; }
    .tbl tbody tr:hover td { background: #FAFBFD; }

    /* Row identity (avatar / icon tile + name + monospace id) */
    .person, .ou { display: flex; align-items: center; gap: .75rem; min-width: 150px; }
    .avatar {
        flex: none; width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center;
        background: var(--accent-soft); color: var(--accent-ink); font-size: .75rem; font-weight: 700;
    }
    .ou-ico {
        flex: none; width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center;
        background: var(--idle-soft); color: #475467; font-size: .85rem;
    }
    .ou-ico.root { background: var(--accent-soft); color: var(--accent-ink); }
    .person .nm, .ou .nm { font-weight: 600; line-height: 1.25; }
    .ou .nm { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; }
    .person .id, .ou .dn { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .76rem; color: var(--muted); }
    .ou .dn { word-break: break-all; }
    .mail, .desc { color: var(--muted); }

    /* Chips */
    .chip { display: inline-block; font-size: .76rem; font-weight: 600; background: var(--idle-soft); color: #475467; padding: .15rem .6rem; border-radius: 999px; }
    .chip.type-student { background: var(--accent-soft); color: var(--accent-ink); }
    .chip.type-staff   { background: var(--good-soft);   color: var(--good); }
    .chip.type-faculty { background: var(--violet-soft); color: var(--violet); }
    .chip.type-guest   { background: var(--warn-soft);   color: var(--warn); }
    .chip.campus       { background: var(--violet-soft); color: var(--violet); }

    /* Row actions */
    .acts { display: flex; justify-content: flex-end; gap: .4rem; white-space: nowrap; }
    .act {
        display: inline-flex; align-items: center; gap: .35rem; font-size: .8rem; font-weight: 600; border: 0; border-radius: 8px;
        padding: .35rem .7rem; text-decoration: none; transition: background .15s;
    }
    .act.edit  { background: var(--accent-soft); color: var(--accent-ink); }
    .act.edit:hover  { background: #D8E3FA; color: var(--accent-ink); }
    .act.reset { background: var(--warn-soft); color: var(--warn); }
    .act.reset:hover { background: #FAE5C4; color: var(--warn); }
    .act.del   { background: var(--bad-soft); color: var(--bad); }
    .act.del:hover   { background: #F8D4D4; color: var(--bad); }
    .lock {
        display: inline-flex; align-items: center; gap: .35rem; font-size: .78rem; font-weight: 600;
        color: var(--muted); background: var(--idle-soft); border-radius: 8px; padding: .35rem .7rem;
    }

    .empty { text-align: center; padding: 3rem 1rem !important; color: var(--muted); }
    .empty i { display: block; font-size: 1.6rem; margin-bottom: .6rem; color: #C2CAD8; }

    .pager { padding: .9rem 1.1rem; border-top: 1px solid var(--line); }
    .pager:empty { display: none; }
    .pager .pagination { margin: 0; justify-content: center; --bs-pagination-active-bg: var(--accent); --bs-pagination-active-border-color: var(--accent); --bs-pagination-color: var(--accent-ink); }

    /* Modal */
    .usr-modal .modal-content { border: 0; border-radius: 16px; box-shadow: 0 20px 50px -12px rgba(23,32,51,.35); }
    .usr-modal .modal-header { border-bottom: 0; padding: 1.25rem 1.4rem .25rem; }
    .usr-modal .modal-title { font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: .6rem; }
    .usr-modal .modal-title .ico {
        width: 32px; height: 32px; border-radius: 9px; display: grid; place-items: center; background: var(--warn-soft); color: var(--warn); font-size: .85rem;
    }
    .usr-modal .modal-title .ico.danger { background: var(--bad-soft); color: var(--bad); }
    .usr-modal .modal-body { padding: .5rem 1.4rem 1rem; font-size: .9rem; }
    .usr-modal .modal-body p { color: #475467; line-height: 1.5; }
    .usr-modal .modal-body code { background: var(--idle-soft); color: var(--ink); border-radius: 5px; padding: .1rem .35rem; word-break: break-all; }
    .usr-modal .form-check-input:checked { background-color: var(--accent); border-color: var(--accent); }
    .usr-modal .form-check-label { font-size: .86rem; color: #475467; }
    .usr-modal .modal-footer { border-top: 0; padding: .25rem 1.4rem 1.25rem; gap: .5rem; }
    .usr-modal .btn-cancel { background: var(--idle-soft); color: #475467; border: 0; border-radius: 8px; font-size: .86rem; font-weight: 600; padding: .5rem 1rem; }
    .usr-modal .btn-cancel:hover { background: #E3E8F0; }
</style>

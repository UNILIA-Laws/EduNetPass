{{-- resources/views/partials/prov-styles.blade.php  (shared by enroll + bulk views) --}}
<style>
    .prov {
        --ink: #172033;
        --muted: #667085;
        --line: #E3E8F0;
        --surface: #FFFFFF;
        --canvas: #F3F5F9;
        --accent: var(--eduroam-blue);
        --accent-soft: #E8EEF6;
        --ok: #178A5C;      --ok-soft: #E3F5EC;
        --bad: #C93A3A;     --bad-soft: #FCE9E9;
        --info: #2557D6;    --info-soft: #E8EEFC;
        --idle: #667085;    --idle-soft: #EEF1F5;
        background: var(--canvas);
        color: var(--ink);
        min-height: 100%;
        padding: 2rem 0 3rem;
    }
    .prov * { box-sizing: border-box; }

    .prov-title { font-size: 1.6rem; font-weight: 700; letter-spacing: -.02em; margin: 0 0 .35rem; }
    .prov-lead  { color: var(--muted); font-size: .95rem; max-width: 42ch; margin: 0 0 1.75rem; line-height: 1.55; }

    /* Numbered steps */
    .flow { list-style: none; margin: 0 0 1.75rem; padding: 0; }
    .flow li { position: relative; display: flex; gap: .85rem; padding-bottom: 1.15rem; }
    .flow li:last-child { padding-bottom: 0; }
    .flow li:not(:last-child)::before {
        content: ""; position: absolute; left: 13px; top: 28px; bottom: 2px; width: 2px; background: var(--line);
    }
    .flow .n {
        flex: none; width: 28px; height: 28px; border-radius: 50%;
        display: grid; place-items: center; font-size: .78rem; font-weight: 700;
        background: var(--accent); color: #fff;
    }
    .flow h3 { font-size: .92rem; font-weight: 650; margin: 3px 0 .15rem; }
    .flow p  { font-size: .84rem; color: var(--muted); margin: 0; line-height: 1.5; }

    /* File format panel (bulk view) */
    .format { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 1rem 1.1rem; }
    .format-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .8rem; }
    .format-head h3 { font-size: .92rem; font-weight: 650; margin: 0; }
    .format-head span { font-size: .78rem; color: var(--muted); }
    .cols { display: flex; flex-wrap: wrap; gap: .4rem; margin: 0; padding: 0; list-style: none; }
    .cols li {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .78rem;
        padding: .25rem .55rem; border-radius: 6px;
        background: var(--accent-soft); color: var(--accent); border: 1px solid transparent;
    }
    .cols li.optional { background: transparent; color: var(--muted); border: 1px dashed #C5CDDA; }
    .format-note { font-size: .78rem; color: var(--muted); margin: .75rem 0 0; line-height: 1.5; }
    .format-note .swatch {
        display: inline-block; width: 22px; height: 10px; border: 1px dashed #C5CDDA; border-radius: 3px; vertical-align: -1px; margin-right: 2px;
    }
    .btn-ghost {
        font-size: .8rem; font-weight: 600; color: var(--accent); background: var(--accent-soft);
        border: 0; border-radius: 8px; padding: .35rem .85rem; text-decoration: none; white-space: nowrap;
        transition: background .15s;
    }
    .btn-ghost:hover { background: #D6E0EE; color: var(--accent); }

    /* Main card */
    .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 1.25rem; }
    @media (min-width: 992px) { .panel { padding: 1.5rem; } }
    .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.1rem; flex-wrap: wrap; }
    .panel-head h2 { font-size: 1.05rem; font-weight: 700; margin: 0; letter-spacing: -.01em; }
    .prov :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

    /* Fields */
    .prov .form-label { font-size: .8rem; font-weight: 600; color: var(--ink); margin-bottom: .3rem; }
    .prov .form-label .hint { color: var(--muted); font-weight: 400; }
    .prov .form-control, .prov .form-select {
        font-size: .88rem; border: 1px solid #D5DBE6; border-radius: 8px; padding: .48rem .7rem; background-color: #fff; color: var(--ink);
        transition: border-color .15s, box-shadow .15s;
    }
    .prov .form-control::placeholder { color: #A5AEBD; }
    .prov .form-control:focus, .prov .form-select:focus {
        border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0,51,102,.12);
    }
    .prov .input-group .btn {
        border: 1px solid #D5DBE6; border-left: 0; color: var(--muted); background: #F7F9FC; border-radius: 0 8px 8px 0; font-size: .8rem;
    }
    .prov .input-group .btn:hover { color: var(--accent); background: var(--accent-soft); }
    .prov .input-group .form-control { border-radius: 8px 0 0 8px; }

    /* Upload zone (bulk view) */
    .drop {
        display: flex; flex-direction: column; align-items: center; gap: .15rem; text-align: center; cursor: pointer;
        border: 1.5px dashed #BFC9DA; border-radius: 10px; background: #FAFBFD; padding: 1.75rem 1rem;
        transition: border-color .15s, background .15s;
    }
    .drop:hover, .drop.drag { border-color: var(--accent); background: var(--accent-soft); }
    .drop strong { font-size: .95rem; }
    .drop small { color: var(--muted); font-size: .8rem; }
    #file-name:empty { display: none; }
    #file-name {
        margin-top: .6rem; font-size: .82rem; font-weight: 600; color: var(--ink); background: var(--idle-soft);
        padding: .25rem .7rem; border-radius: 6px;
    }

    /* Primary button */
    .prov .btn-eduroam {
        background: var(--accent); color: #fff; border: 0; border-radius: 8px;
        font-size: .9rem; font-weight: 600; padding: .65rem 1rem; box-shadow: none; transition: background .15s;
    }
    .prov .btn-eduroam:hover { background: #002244; color: #fff; }
    .prov .btn-eduroam:disabled { background: #6B87A6; opacity: 1; }

    .notice {
        background: var(--bad-soft); color: #8E2323; border-radius: 8px; padding: .65rem .85rem; font-size: .86rem; margin-bottom: 1rem;
    }

    /* Results */
    .results { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--line); }
    .results h3 { font-size: .95rem; font-weight: 650; margin: 0 0 .75rem; }
    .tally { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .9rem; }
    .tally div { display: flex; align-items: baseline; gap: .4rem; padding: .4rem .75rem; border-radius: 8px; font-size: .82rem; }
    .tally b { font-size: 1.15rem; font-weight: 700; }
    .tally .ok  { background: var(--ok-soft);  color: var(--ok); }
    .tally .bad { background: var(--bad-soft); color: var(--bad); }
    .tally .none { background: var(--idle-soft); color: var(--idle); }

    .tbl-wrap { max-height: 360px; overflow: auto; border: 1px solid var(--line); border-radius: 10px; }
    .tbl { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .82rem; }
    .tbl th {
        position: sticky; top: 0; background: #F7F9FC; text-align: left; font-weight: 600; color: var(--muted);
        padding: .55rem .8rem; border-bottom: 1px solid var(--line); white-space: nowrap;
    }
    .tbl td { padding: .6rem .8rem; border-bottom: 1px solid #EEF1F6; vertical-align: top; }
    .tbl tr:last-child td { border-bottom: 0; }
    .tbl .sub { color: var(--muted); font-size: .76rem; }
    .tbl .num { color: var(--muted); }
    .tbl code { background: var(--idle-soft); color: var(--ink); border-radius: 5px; padding: .1rem .35rem; }
    .tbl .err { color: var(--bad); }

    .pill {
        display: inline-flex; align-items: center; gap: .35rem; padding: .15rem .6rem; border-radius: 999px;
        font-size: .76rem; font-weight: 600;
    }
    .pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .pill.ok   { background: var(--ok-soft);   color: var(--ok); }
    .pill.info { background: var(--info-soft); color: var(--info); }
    .pill.bad  { background: var(--bad-soft);  color: var(--bad); }
    .pill.idle { background: var(--idle-soft); color: var(--idle); }
</style>

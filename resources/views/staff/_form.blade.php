@php $u = $user ?? null; @endphp

<style>
    .usr .input-group > .form-control { border-radius: 8px 0 0 8px; }
    .usr .input-group .btn-tool {
        display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; font-weight: 600; color: var(--accent-ink);
        background: var(--accent-soft); border: 1px solid #D5DBE6; border-left: 0; padding: 0 .75rem; white-space: nowrap;
    }
    .usr .input-group .btn-tool:hover { background: #D8E3FA; }
    .usr .input-group .btn-tool:last-of-type { border-radius: 0 8px 8px 0; }
    .usr .switch-row {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        border: 1px solid var(--line); border-radius: 10px; padding: .55rem .9rem; background: #FAFBFD;
    }
    .usr .switch-row .ttl { font-weight: 600; font-size: .86rem; line-height: 1.2; }
    .usr .switch-row .sub { color: var(--muted); font-size: .78rem; }
    .usr .switch-row .form-check-input { width: 2.5em; height: 1.3em; margin: 0; cursor: pointer; }
    .usr .form-check-input:checked { background-color: var(--accent); border-color: var(--accent); }
    .usr .form-label { margin-bottom: .25rem; }
    @media (max-width: 575.98px) { .usr .input-group .btn-tool span, .usr .input-group .btn-tool .lbl { display: none; } }
</style>

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">Full name</label>
        <input id="name" name="name" value="{{ old('name', $u?->name) }}" placeholder="e.g. Grace Banda"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="email">E-mail <span class="hint">(login)</span></label>
        <input id="email" type="email" name="email" value="{{ old('email', $u?->email) }}" placeholder="name@example.org"
               class="form-control @error('email') is-invalid @enderror" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="password">
            Password
            @if($u)<span class="hint">(blank = keep current)</span>@endif
        </label>
        <div class="input-group">
            <input id="password" type="text" name="password" minlength="8" autocomplete="new-password"
                   placeholder="At least 8 characters"
                   class="form-control @error('password') is-invalid @enderror" @unless($u) required @endunless>
            <button type="button" class="btn-tool" id="genPwd" title="Generate a password" aria-label="Generate a password">
                <i class="fa-solid fa-wand-magic-sparkles"></i><span class="lbl">Generate</span>
            </button>
            <button type="button" class="btn-tool" id="copyPwd" title="Copy password" aria-label="Copy password">
                <i class="fa-solid fa-copy"></i><span class="lbl" id="copyLbl">Copy</span>
            </button>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="campus_id">Campus <span class="hint">(optional)</span></label>
        <select id="campus_id" name="campus_id" class="form-select @error('campus_id') is-invalid @enderror">
            <option value="">No campus (all campuses)</option>
            @foreach($campuses as $c)
                <option value="{{ $c->id }}" @selected((string) old('campus_id', $u?->campus_id) === (string) $c->id)>
                    {{ $c->label }}{{ $c->is_active ? '' : ' (disabled)' }}
                </option>
            @endforeach
        </select>
        @error('campus_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <div class="switch-row">
            <div>
                <div class="ttl">Administrator</div>
                <div class="sub">Can manage campuses and staff accounts.</div>
            </div>
            <div class="form-check form-switch m-0 p-0">
                <input type="hidden" name="is_admin" value="0">
                <input class="form-check-input @error('is_admin') is-invalid @enderror" type="checkbox" role="switch"
                       id="is_admin" name="is_admin" value="1" aria-label="Administrator" @checked(old('is_admin', $u?->is_admin))>
            </div>
        </div>
        @error('is_admin')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const pwd = document.getElementById('password');

        document.getElementById('genPwd').addEventListener('click', () => {
            const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            const rnd = crypto.getRandomValues(new Uint32Array(12));
            pwd.value = Array.from(rnd, n => chars[n % chars.length]).join('');
            pwd.focus();
        });

        document.getElementById('copyPwd').addEventListener('click', async () => {
            if (!pwd.value) { pwd.focus(); return; }
            try { await navigator.clipboard.writeText(pwd.value); }
            catch (e) { pwd.select(); document.execCommand('copy'); }
            const l = document.getElementById('copyLbl');
            l.textContent = 'Copied';
            setTimeout(() => l.textContent = 'Copy', 1500);
        });
    })();
</script>
@endpush
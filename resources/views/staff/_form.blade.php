@php $u = $user ?? null; @endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label small fw-semibold" for="name">Full name</label>
        <input id="name" name="name" value="{{ old('name', $u?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold" for="email">E-mail (login)</label>
        <input id="email" type="email" name="email" value="{{ old('email', $u?->email) }}"
               class="form-control @error('email') is-invalid @enderror" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold" for="password">
            Password
            @if($u)<span class="text-muted fw-normal">(leave blank to keep current)</span>@endif
        </label>
        <div class="input-group">
            <input id="password" type="text" name="password" minlength="8" autocomplete="new-password"
                   class="form-control @error('password') is-invalid @enderror" @unless($u) required @endunless>
            <button type="button" class="btn btn-outline-secondary" id="genPwd"
                    title="Generate a password" aria-label="Generate a password">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </button>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">At least 8 characters. Share it with the person securely.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold" for="campus_id">
            Campus <span class="text-muted fw-normal">(optional)</span>
        </label>
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
        <div class="form-check form-switch">
            <input type="hidden" name="is_admin" value="0">
            <input class="form-check-input @error('is_admin') is-invalid @enderror" type="checkbox" role="switch"
                   id="is_admin" name="is_admin" value="1" @checked(old('is_admin', $u?->is_admin))>
            <label class="form-check-label fw-semibold small" for="is_admin">Administrator</label>
            @error('is_admin')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">Administrators can manage campuses and staff accounts.</div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('genPwd').addEventListener('click', () => {
        const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        const rnd = crypto.getRandomValues(new Uint32Array(12));
        document.getElementById('password').value = Array.from(rnd, n => chars[n % chars.length]).join('');
    });
</script>
@endpush
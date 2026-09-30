@extends('layouts.app')
@section('title', 'eduroam | Login')

@section('content')
<style>
    .auth {
        --ink: #172033;
        --muted: #667085;
        --line: #E3E8F0;
        --surface: #FFFFFF;
        --canvas: #F3F5F9;
        --accent: #2557D6;
        --accent-ink: #1B44AB;
        --accent-soft: #E8EEFC;
        --bad: #C93A3A;
        --bad-soft: #FCE9E9;
        background: var(--canvas);
        color: var(--ink);
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        min-height: calc(100vh - 120px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2.5rem 1rem;
    }
    .auth-box { width: 100%; max-width: 380px; }
    .auth-head { text-align: center; }

    .auth-mark {
        width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center;
        background: var(--accent); color: #fff; font-size: 1.1rem; margin: 0 auto 1.1rem;
    }
    .auth h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: -.02em; margin: 0 0 .3rem; }
    .auth-lead { color: var(--muted); font-size: .92rem; line-height: 1.5; margin: 0 0 1.5rem; }

    .auth-card {
        background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 1.5rem;
        box-shadow: 0 1px 2px rgba(23,32,51,.04), 0 8px 24px -12px rgba(23,32,51,.12);
    }

    .auth .form-label { font-size: .8rem; font-weight: 600; color: var(--ink); margin-bottom: .3rem; }
    .auth .form-control {
        font-size: .9rem; border: 1px solid #D5DBE6; border-radius: 8px; padding: .55rem .75rem; background: #fff; color: var(--ink);
        transition: border-color .15s, box-shadow .15s;
    }
    .auth .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,87,214,.15); }
    .auth :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

    .auth .input-group .form-control { border-radius: 8px 0 0 8px; }
    .auth .input-group .btn {
        border: 1px solid #D5DBE6; border-left: 0; color: var(--muted); background: #F7F9FC; border-radius: 0 8px 8px 0; padding: 0 .8rem;
    }
    .auth .input-group .btn:hover { color: var(--accent); background: var(--accent-soft); }

    .auth .form-check-input:checked { background-color: var(--accent); border-color: var(--accent); }
    .auth .form-check-label { font-size: .84rem; color: var(--muted); }

    .auth .btn-eduroam {
        background: var(--accent); color: #fff; border: 0; border-radius: 10px;
        font-size: .92rem; font-weight: 600; padding: .65rem 1rem; box-shadow: none; transition: background .15s;
    }
    .auth .btn-eduroam:hover { background: var(--accent-ink); color: #fff; }

    .auth-error {
        display: flex; gap: .55rem; align-items: flex-start; background: var(--bad-soft); color: #8E2323;
        border-radius: 10px; padding: .6rem .8rem; font-size: .84rem; margin-bottom: 1rem;
    }
    .auth-foot { text-align: center; color: var(--muted); font-size: .78rem; margin: 1.1rem 0 0; }
</style>

<div class="auth">
    <div class="auth-box">
        <div class="auth-head">
            <div class="auth-mark"><i class="fa-solid fa-wifi"></i></div>
            <h1>Sign in</h1>
            <p class="auth-lead">ICT staff only. Use your staff e-mail to manage eduroam accounts.</p>
        </div>

        <div class="auth-card">
            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf

                @if($errors->has('email') || $errors->has('password'))
                    <div class="auth-error" role="alert">
                        <i class="fa-solid fa-circle-exclamation mt-1"></i>
                        <span>{{ $errors->first('email') ?: $errors->first('password') }}</span>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username"
                           class="form-control @error('email') is-invalid @enderror" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" autocomplete="current-password" class="form-control" required>
                        <button type="button" class="btn" id="togglePwd" aria-label="Show password" title="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Keep me signed in on this device</label>
                </div>

                <div class="d-grid"><button type="submit" class="btn btn-eduroam">Sign in</button></div>
            </form>
        </div>

        <p class="auth-foot">Having trouble signing in? Contact the ICT service desk.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const pwd = document.getElementById('password');
    const toggle = document.getElementById('togglePwd');
    toggle.addEventListener('click', () => {
        const show = pwd.type === 'password';
        pwd.type = show ? 'text' : 'password';
        toggle.querySelector('i').className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        toggle.title = show ? 'Hide password' : 'Show password';
    });
</script>
@endpush
@extends('layouts.app')
@section('title', 'eduroam | Dashboard')

@push('styles')
<style>
    .dash { --blue: var(--eduroam-blue); --ink: #172033; --muted: #667085; --line: #E3E8F0; }

    .dash-hero {
        border-radius: 16px; color: #fff; padding: 1.75rem 2rem;
        background: var(--blue);
    }
    .dash-hero h1 { font-size: 1.6rem; font-weight: 700; letter-spacing: -.02em; margin: 0 0 .25rem; }
    .dash-hero p  { margin: 0; color: rgba(255,255,255,.8); }
    .dash-hero .btn-refresh {
        color: #fff; border: 1px solid rgba(255,255,255,.35); border-radius: 8px;
        font-size: .82rem; font-weight: 600; padding: .4rem .9rem; text-decoration: none; transition: background .15s;
    }
    .dash-hero .btn-refresh:hover { background: rgba(255,255,255,.12); color: #fff; }

    /* Stat tiles */
    .stat {
        background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 1.15rem 1.35rem; height: 100%;
    }
    .stat .num   { font-size: 1.85rem; font-weight: 700; line-height: 1; color: var(--ink); letter-spacing: -.02em; }
    .stat .label { font-size: .82rem; color: var(--muted); margin-top: .4rem; }

    /* Panels */
    .dpanel { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 1.25rem 1.35rem; height: 100%; }
    .dpanel h2 { font-size: 1rem; font-weight: 700; color: var(--ink); margin: 0 0 1.1rem; }

    .campus-row + .campus-row { margin-top: 1rem; }
    .campus-row .top { display: flex; justify-content: space-between; font-size: .88rem; margin-bottom: .35rem; }
    .campus-row .top b { color: var(--ink); font-weight: 600; }
    .campus-row .top span { color: var(--muted); }
    .bar { height: 6px; background: #EEF1F5; border-radius: 999px; overflow: hidden; }
    .bar > div { height: 100%; border-radius: 999px; background: var(--blue); }

    /* Action links */
    .action {
        display: flex; align-items: center; gap: 1rem; background: #fff; border: 1px solid var(--line); border-radius: 10px;
        padding: 1rem 1.15rem; text-decoration: none; color: inherit; height: 100%;
        transition: border-color .15s, background .15s;
    }
    .action:hover { border-color: var(--blue); background: #F7F9FC; color: inherit; }
    .action h3 { font-size: .95rem; font-weight: 650; margin: 0 0 .15rem; color: var(--ink); }
    .action p  { font-size: .8rem; color: var(--muted); margin: 0; line-height: 1.4; }
    .action .go { margin-left: auto; font-size: .8rem; color: #A5AEBD; transition: transform .15s, color .15s; }
    .action:hover .go { transform: translateX(3px); color: var(--blue); }

    .dash-alert { background: #FCE9E9; color: #8E2323; border-radius: 10px; padding: .75rem 1rem; font-size: .88rem; }
</style>
@endpush

@section('content')
@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $total = $stats['total'] ?? 0;
@endphp

<div class="dash container py-4 py-lg-5">

    {{-- Header --}}
    <div class="dash-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1>{{ $greeting }}, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}</h1>
                <p>An overview of the LDAP accounts today.</p>
            </div>
            <a href="{{ route('dashboard', ['refresh' => 1]) }}" class="btn-refresh">
                <i class="fa-solid fa-rotate me-1"></i> Refresh
                @if($stats) <span class="opacity-75">· updated {{ $stats['generated_at'] }}</span> @endif
            </a>
        </div>
    </div>

    @if($error)
        <div class="dash-alert mb-4">
            Could not load statistics from LDAP: {{ $error }}
        </div>
    @endif

    {{-- Stat tiles --}}
    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat">
                <div class="num">{{ $stats ? number_format($total) : '—' }}{{ !empty($stats['capped']) ? '+' : '' }}</div>
                <div class="label">Total LDAP users</div>
            </div>
        </div>

        @foreach(($stats['campuses'] ?? []) as $c)
            <div class="col-6 col-xl-3">
                <div class="stat">
                    <div class="num">{{ number_format($c['count']) }}</div>
                    <div class="label">{{ $c['label'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 g-lg-4">

        {{-- Campus distribution --}}
        <div class="col-lg-5">
            <div class="dpanel">
                <h2>Users by campus</h2>

                @forelse(($stats['campuses'] ?? []) as $c)
                    @php $pct = $total > 0 ? round($c['count'] / $total * 100) : 0; @endphp
                    <div class="campus-row">
                        <div class="top"><b>{{ $c['label'] }}</b><span>{{ number_format($c['count']) }} · {{ $pct }}%</span></div>
                        <div class="bar"><div style="width: {{ $pct }}%"></div></div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No data available yet.</p>
                @endforelse

                @if(!empty($stats['capped']))
                    <p class="text-muted small mt-3 mb-0">
                        The count is capped at {{ number_format(config('ldap.max_list')) }}, so the real total may be higher.
                    </p>
                @endif
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="col-lg-7">
            <div class="dpanel">
                <h2>Quick actions</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="{{ route('enroll') }}" class="action">
                            <div>
                                <h3>Add LDAP users</h3>
                                <p>One by one or from a CSV/Excel file.</p>
                            </div>
                            <i class="fa-solid fa-arrow-right go"></i>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="{{ route('users.index') }}" class="action">
                            <div>
                                <h3>View LDAP users</h3>
                                <p>Search, edit, reset passwords, delete.</p>
                            </div>
                            <i class="fa-solid fa-arrow-right go"></i>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="{{ route('download.sample') }}" class="action">
                            <div>
                                <h3>Sample file</h3>
                                <p>Download the bulk upload template.</p>
                            </div>
                            <i class="fa-solid fa-arrow-right go"></i>
                        </a>
                    </div>

                    @if(auth()->user()->is_admin && Route::has('settings.edit'))
                        <div class="col-md-6">
                            <a href="{{ route('settings.edit') }}" class="action">
                                <div>
                                    <h3>Settings</h3>
                                    <p>Campuses, entitlements and limits.</p>
                                </div>
                                <i class="fa-solid fa-arrow-right go"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
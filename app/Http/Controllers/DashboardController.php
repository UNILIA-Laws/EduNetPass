<?php

namespace App\Http\Controllers;

use App\Services\LdapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardController extends Controller
{
    private const CACHE_KEY = 'dashboard.stats';
    private const CACHE_TTL = 300; // seconds

    public function __construct(private LdapService $ldap)
    {
    }

    public function index(Request $request)
    {
        if ($request->boolean('refresh')) {
            Cache::forget(self::CACHE_KEY);
        }

        $stats = Cache::get(self::CACHE_KEY);
        $error = null;

        if ($stats === null) {
            try {
                $stats = $this->collectStats();
                Cache::put(self::CACHE_KEY, $stats, self::CACHE_TTL);
            } catch (Throwable $e) {
                $error = $e->getMessage();
                Log::error('MyLDAP.dashboard stats failed', ['error' => $error]);
            }
        }

        return view('dashboard', [
            'stats' => $stats,
            'error' => $error,
        ]);
    }

    private function collectStats(): array
    {
        $counts = $this->ldap->countStudents();

        $campuses = [];
        $assigned = 0;

     
        foreach ($this->ldap->campuses() as $key => $c) {
            $count     = $counts['byCampus'][LdapService::normalizeDn($c['dn'])] ?? 0;
            $assigned += $count;

            $campuses[] = [
                'key'   => $key,
                'label' => $c['label'],
                'count' => $count,
            ];
        }

        return [
            'total'        => $counts['total'],
            'campuses'     => $campuses,
            'unassigned'   => max(0, $counts['total'] - $assigned),
            'byType'       => $counts['byType'],
            'capped'       => false,
            'generated_at' => now()->format('H:i'),
        ];
    }
}
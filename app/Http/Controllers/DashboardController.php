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
                Log::error('eduroam.dashboard stats failed', ['error' => $error]);
            }
        }

        return view('dashboard', [
            'stats' => $stats,
            'error' => $error,
        ]);
    }

    private function collectStats(): array
    {
        $norm   = fn (string $s) => strtolower(preg_replace('/\s*,\s*/', ',', trim($s)));
        $counts = $this->ldap->countStudents();

        $campuses = [];
        foreach (config('ldap.campuses') as $key => $c) {
            $campuses[] = [
                'key'   => $key,
                'label' => $c['label'],
                'count' => $counts['byCampus'][$norm($c['dn'])] ?? 0,
            ];
        }

        return [
            'total'        => $counts['total'],
            'campuses'     => $campuses,
            'capped'       => false,
            'generated_at' => now()->format('H:i'),
        ];
    }
}
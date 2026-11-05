<?php

namespace App\Http\Controllers;

use App\Services\LdapDiagnostics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Read-only view of the LDAP server settings.
 *
 * The real values live in .env (read through config/ldap.php). This page never
 * edits them and never displays the bind password.
 */
class LdapSettingsController extends Controller
{
    public function show(Request $request)
    {
        $uri   = (string) config('ldap.uri');
        $parts = parse_url($uri) ?: [];

        $scheme    = strtolower($parts['scheme'] ?? '');
        $startTls  = (bool) config('ldap.start_tls');
        $encrypted = $scheme === 'ldaps' || $startTls;
        $hasPass   = (string) config('ldap.bind_password') !== '';

        $settings = [
            'Server'               => $parts['host'] ?? '(not set)',
            'Port'                 => $parts['port'] ?? ($scheme === 'ldaps' ? 636 : 389),
            'Protocol'             => $scheme !== '' ? $scheme . '://' : '(not set)',
            'StartTLS'             => $startTls ? 'Enabled' : 'Disabled',
            'Bind DN'              => (string) config('ldap.bind_dn') ?: '(not set)',
            'Bind password'        => $hasPass ? 'Set (hidden)' : 'NOT SET',
            'Network timeout'      => (int) config('ldap.timeout') . ' s',
            'People container'     => (string) config('ldap.people_dn') ?: '(not set)',
            'Campuses container'   => (string) config('ldap.campuses_dn') ?: '(not set)',
            'Page size'            => (int) config('ldap.page_size'),
        ];

        $warnings = [];
        if (! $encrypted) {
            $warnings[] = 'The connection is not encrypted. Set LDAP_URI to an ldaps:// address or enable LDAP_START_TLS=true.';
        }
        if (! $hasPass) {
            $warnings[] = 'LDAP_BIND_PASSWORD is empty. Set it in .env.';
        }
        if ($uri === '' || (string) config('ldap.bind_dn') === '') {
            $warnings[] = 'LDAP_URI and LDAP_BIND_DN must be set in .env.';
        }
        if (config('app.debug') && app()->environment('production')) {
            $warnings[] = 'APP_DEBUG is on in production. Turn it off: error pages can leak configuration.';
        }

        return view('settings.ldap', [
            'settings'  => $settings,
            'encrypted' => $encrypted,
            'warnings'  => $warnings,
            'test'      => $request->session()->get('ldap_test'),
        ]);
    }

    public function test(Request $request, LdapDiagnostics $diagnostics): RedirectResponse
    {
        try {
            $result = $diagnostics->run();
        } catch (Throwable $e) {
            Log::error('eduroam.ldap_settings test crashed', ['error' => $e->getMessage()]);
            $result = [
                'ok'        => false,
                'ms'        => 0,
                'encrypted' => false,
                'checks'    => [['name' => 'Diagnostic', 'ok' => false, 'detail' => 'Unexpected error. See the application log.']],
            ];
        }

        // Audit trail: who ran the test and what the outcome was (never the password).
        Log::info('eduroam.ldap_settings test', [
            'user_id' => $request->user()?->getAuthIdentifier(),
            'ip'      => $request->ip(),
            'ok'      => $result['ok'],
        ]);

        return redirect()->route('settings.ldap')->with('ldap_test', $result);
    }
}
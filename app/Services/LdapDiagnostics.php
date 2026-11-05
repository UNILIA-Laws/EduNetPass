<?php

namespace App\Services;

/**
 * Runs a step-by-step health check of the LDAP connection using the values
 * from config/ldap.php (which come from .env).
 *
 * It opens its OWN short-lived connection, so it never touches the one used by
 * LdapService, and it never returns or logs the bind password.
 */
class LdapDiagnostics
{
    /**
     * @return array{ok:bool, ms:int, encrypted:bool, checks:list<array{name:string,ok:bool,detail:string}>}
     */
    public function run(): array
    {
        $started   = microtime(true);
        $checks    = [];
        $encrypted = false;
        $conn      = null;

        $add = function (string $name, bool $ok, string $detail = '') use (&$checks): bool {
            $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
            return $ok;
        };

        $finish = function () use (&$checks, &$encrypted, $started, &$conn): array {
            if ($conn) {
                @ldap_unbind($conn);
            }
            return [
                'ok'        => $checks !== [] && ! in_array(false, array_column($checks, 'ok'), true),
                'ms'        => (int) round((microtime(true) - $started) * 1000),
                'encrypted' => $encrypted,
                'checks'    => $checks,
            ];
        };

        // 1. PHP extension
        if (! $add('PHP LDAP extension', extension_loaded('ldap'),
            extension_loaded('ldap') ? 'Loaded' : 'Enable "extension=ldap" in php.ini')) {
            return $finish();
        }

        // 2. Required settings present
        $uri      = (string) config('ldap.uri');
        $bindDn   = (string) config('ldap.bind_dn');
        $password = (string) config('ldap.bind_password');

        $missing = array_keys(array_filter([
            'LDAP_URI'           => $uri === '',
            'LDAP_BIND_DN'       => $bindDn === '',
            'LDAP_BIND_PASSWORD' => $password === '',
        ]));
        if (! $add('Required .env values', $missing === [],
            $missing === [] ? 'LDAP_URI, LDAP_BIND_DN and LDAP_BIND_PASSWORD are set'
                            : 'Missing: ' . implode(', ', $missing))) {
            // An empty password must never reach ldap_bind(): many servers treat
            // "DN + empty password" as an unauthenticated (anonymous) bind that "succeeds".
            return $finish();
        }

        // 3. Connect
        $conn = @ldap_connect($uri);
        if (! $add('Connection string', $conn !== false, $conn !== false ? $uri : 'Invalid LDAP_URI')) {
            $conn = null;
            return $finish();
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, (int) config('ldap.timeout'));

        // 4. Transport encryption
        $scheme    = strtolower((string) (parse_url($uri, PHP_URL_SCHEME) ?? ''));
        $encrypted = $scheme === 'ldaps';

        if (config('ldap.start_tls')) {
            $encrypted = @ldap_start_tls($conn);
            if (! $add('StartTLS', $encrypted, $encrypted ? 'Connection upgraded to TLS' : 'StartTLS failed: ' . ldap_error($conn))) {
                return $finish();
            }
        } elseif ($encrypted) {
            $add('TLS (ldaps://)', true, 'Encrypted connection');
        } else {
            // Not a failure of the test itself, but a real security problem.
            $add('Transport encryption', false,
                'Traffic is NOT encrypted. Passwords are sent in clear text. Use ldaps:// or LDAP_START_TLS=true.');
        }

        // 5. Bind (authenticate)
        if (! $add('Bind as service account', @ldap_bind($conn, $bindDn, $password),
            @ldap_errno($conn) === 0 ? 'Authenticated' : 'Bind failed: ' . ldap_error($conn))) {
            return $finish();
        }

        // 6. Containers readable
        foreach (['people_dn' => 'People container', 'campuses_dn' => 'Campuses container'] as $key => $label) {
            $dn = (string) config("ldap.{$key}");
            if ($dn === '') {
                $add($label, false, "ldap.{$key} is not set");
                continue;
            }
            $res = @ldap_read($conn, $dn, '(objectClass=*)', ['dn']);
            $add($label, $res !== false,
                $res !== false ? 'Readable' : 'Cannot read ' . $dn . ': ' . ldap_error($conn));
        }

        return $finish();
    }
}
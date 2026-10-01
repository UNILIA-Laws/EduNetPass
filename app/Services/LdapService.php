<?php

namespace App\Services;

use RuntimeException;

/**
 * Thin wrapper around PHP's ldap_* functions.
 */
class LdapService
{
    /** @var \LDAP\Connection|null */
    private $conn = null;

    public function __destruct()
    {
        $this->disconnect();
    }

    public function connect(): void
    {
        if (! extension_loaded('ldap')) {
            throw new RuntimeException('The PHP LDAP extension is not enabled (enable "extension=ldap" in php.ini).');
        }

        $conn = @ldap_connect(config('ldap.uri'));
        if ($conn === false) {
            throw new RuntimeException('Invalid LDAP URI: ' . config('ldap.uri'));
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, (int) config('ldap.timeout'));

        if (config('ldap.start_tls') && ! @ldap_start_tls($conn)) {
            throw new RuntimeException('LDAP StartTLS failed: ' . ldap_error($conn));
        }

        if (! @ldap_bind($conn, config('ldap.bind_dn'), config('ldap.bind_password'))) {
            throw new RuntimeException('Could not connect/bind to LDAP: ' . ldap_error($conn));
        }

        $this->conn = $conn;
    }

    public function disconnect(): void
    {
        if ($this->conn) {
            @ldap_unbind($this->conn);
            $this->conn = null;
        }
    }

    private function ensureConnected(): void
    {
        if (! $this->conn) {
            $this->connect();
        }
    }

    private function dnFor(string $uid): string
    {
        return 'uid=' . ldap_escape($uid, '', LDAP_ESCAPE_DN) . ',' . config('ldap.people_dn');
    }

    /* ------------------------------------------------------------------
     |  Create / update (used by bulk upload and the single-student form)
     * -----------------------------------------------------------------*/

    /**
     * Create the user, or update it if the uid already exists.
     *
     * @param array{uid:string,givenName:string,sn:string,mail:string,password:string} $u
     * @return string 'created' | 'updated'
     */
    public function upsertStudent(array $u, string $campusDn): string
    {
        $this->ensureConnected();

        $dn = $this->dnFor($u['uid']);
        $cn = trim($u['givenName'] . ' ' . $u['sn']);

        $attrs = [
            'sn'                   => $u['sn'],
            'givenName'            => $u['givenName'],
            'cn'                   => $cn,
            'displayName'          => $cn,
            'preferredLanguage'    => 'en',
            'userPassword'         => self::ssha($u['password']),
            'mail'                 => $u['mail'],
            'eduPersonOrgUnitDN'   => $campusDn,
            'eduPersonAffiliation' => ['student'],
            'eduPersonEntitlement' => array_values(config('ldap.entitlements')),
        ];

        if ($this->exists($dn)) {
            if (! @ldap_modify($this->conn, $dn, $attrs)) {
                throw new RuntimeException('LDAP modify failed: ' . ldap_error($this->conn));
            }
            return 'updated';
        }

        $attrs['objectClass'] = ['inetOrgPerson', 'eduPerson', 'top'];
        $attrs['uid']         = $u['uid'];

        if (! @ldap_add($this->conn, $dn, $attrs)) {
            throw new RuntimeException('LDAP add failed: ' . ldap_error($this->conn));
        }
        return 'created';
    }

    /* ------------------------------------------------------------------
     |  Read
     * -----------------------------------------------------------------*/

    /**
     * List users under the people container (handles servers with a size limit
     * by using paged results).
     *
     * @param string      $q        free text: matches uid, name or e-mail
     * @param string|null $campusDn only users of this campus
     * @return list<array<string,mixed>> sorted by uid
     */
    public function searchStudents(string $q = '', ?string $campusDn = null): array
    {
        $this->ensureConnected();

        $filter = '(objectClass=inetOrgPerson)';
        if ($q !== '') {
            $e = ldap_escape($q, '', LDAP_ESCAPE_FILTER);
            $filter = "(&{$filter}(|(uid=*{$e}*)(cn=*{$e}*)(mail=*{$e}*)))";
        }

        $attrs = ['uid', 'givenName', 'sn', 'cn', 'mail', 'eduPersonOrgUnitDN', 'eduPersonAffiliation'];
        $max   = (int) config('ldap.max_list');
        $users = [];
        $cookie = '';

        do {
            $res = @ldap_search(
                $this->conn, config('ldap.people_dn'), $filter, $attrs, 0, 0, 0, LDAP_DEREF_NEVER,
                [['oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => ['size' => (int) config('ldap.page_size'), 'cookie' => $cookie]]]
            );
            if ($res === false) {
                throw new RuntimeException('LDAP search failed: ' . ldap_error($this->conn));
            }

            ldap_parse_result($this->conn, $res, $code, $matched, $message, $referrals, $controls);

            $entries = ldap_get_entries($this->conn, $res);
            for ($i = 0; $i < $entries['count']; $i++) {
                $user = $this->entryToArray($entries[$i]);
                if ($campusDn && ! $this->sameDn($user['campusDn'], $campusDn)) {
                    continue;
                }
                $users[] = $user;
            }

            $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'] ?? '';
        } while ($cookie !== '' && count($users) < $max);

        usort($users, fn ($a, $b) => strcasecmp($a['uid'], $b['uid']));

        return $users;
    }

    /** @return array<string,mixed>|null */
    public function findStudent(string $uid): ?array
    {
        $this->ensureConnected();

        $res = @ldap_read(
            $this->conn, $this->dnFor($uid), '(objectClass=*)',
            ['uid', 'givenName', 'sn', 'cn', 'mail', 'eduPersonOrgUnitDN', 'eduPersonAffiliation']
        );
        if ($res === false) {
            if (ldap_errno($this->conn) === 32) {
                return null;
            }
            throw new RuntimeException('LDAP lookup failed: ' . ldap_error($this->conn));
        }

        $entries = ldap_get_entries($this->conn, $res);
        return $entries['count'] > 0 ? $this->entryToArray($entries[0]) : null;
    }

    /* ------------------------------------------------------------------
     |  Edit / reset password
     * -----------------------------------------------------------------*/

    /**
     * Change name, e-mail and campus. The username (uid) cannot be changed.
     *
     * @param array{givenName:string,sn:string,mail:string} $c
     */
    public function updateStudent(string $uid, array $c, string $campusDn): void
    {
        $this->ensureConnected();

        $cn = trim($c['givenName'] . ' ' . $c['sn']);

        $ok = @ldap_modify($this->conn, $this->dnFor($uid), [
            'givenName'          => $c['givenName'],
            'sn'                 => $c['sn'],
            'cn'                 => $cn,
            'displayName'        => $cn,
            'mail'               => $c['mail'],
            'eduPersonOrgUnitDN' => $campusDn,
        ]);

        if (! $ok) {
            throw new RuntimeException('LDAP update failed: ' . ldap_error($this->conn));
        }
    }

    public function setPassword(string $uid, string $password): void
    {
        $this->ensureConnected();

        if (! @ldap_modify($this->conn, $this->dnFor($uid), ['userPassword' => self::ssha($password)])) {
            throw new RuntimeException('Password reset failed: ' . ldap_error($this->conn));
        }
    }


    public function deleteStudent(string $uid): void
    {
        $this->ensureConnected();

        if (! @ldap_delete($this->conn, $this->dnFor($uid))) {
            // 32 = No such object (already gone)
            if (ldap_errno($this->conn) === 32) {
                throw new RuntimeException('User not found in LDAP.');
            }
            throw new RuntimeException('Delete failed: ' . ldap_error($this->conn));
        }
    }

    /* ------------------------------------------------------------------
     |  Helpers
     * -----------------------------------------------------------------*/

    private function exists(string $dn): bool
    {
        $res = @ldap_read($this->conn, $dn, '(objectClass=*)', ['uid']);

        if ($res === false) {
            if (ldap_errno($this->conn) === 32) { // 32 = No such object
                return false;
            }
            throw new RuntimeException('LDAP lookup failed: ' . ldap_error($this->conn));
        }

        return ldap_count_entries($this->conn, $res) > 0;
    }

    private function entryToArray(array $e): array
    {
        $get = fn (string $k) => $e[$k][0] ?? '';

        return [
            'uid'         => $get('uid'),
            'givenName'   => $get('givenname'),
            'sn'          => $get('sn'),
            'name'        => $get('cn'),
            'mail'        => $get('mail'),
            'campusDn'    => $get('edupersonorgunitdn'),
            'affiliation' => $get('edupersonaffiliation'),
        ];
    }

    private function sameDn(string $a, string $b): bool
    {
        $norm = fn (string $s) => strtolower(preg_replace('/\s*,\s*/', ',', trim($s)));
        return $norm($a) === $norm($b);
    }

    /** Salted SHA-1 ({SSHA}). */
    public static function ssha(string $plain): string
    {
        $salt = random_bytes(4);
        return '{SSHA}' . base64_encode(sha1($plain . $salt, true) . $salt);
    }
}
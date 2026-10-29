<?php

namespace App\Services;

use RuntimeException;

/**
 * Thin wrapper around PHP's ldap_* functions.
 * Handles several user types (student, staff, faculty, guest...) that all live
 * under the same people container and are told apart by eduPersonAffiliation.
 * Campuses are read from the directory (OUs under ldap.campuses_dn).
 */
class LdapService
{
    /** @var \LDAP\Connection|null */
    protected $conn = null;

    /** @var array<string,array{label:string,dn:string}>|null per-instance cache */
    protected ?array $campusCache = null;

    public function __destruct()
    {
        $this->disconnect();
    }

    public function connect(): void
    {
        if (! extension_loaded('ldap')) {
            throw new RuntimeException('The PHP LDAP extension is not enabled (enable "extension=ldap" in php.ini).');
        }

        $this->disconnect(); // never leak an earlier connection

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

    protected function ensureConnected(): void
    {
        if (! $this->conn) {
            $this->connect();
        }
    }

    protected function dnFor(string $uid): string
    {
        return 'uid=' . ldap_escape($uid, '', LDAP_ESCAPE_DN) . ',' . config('ldap.people_dn');
    }

    /* ------------------------------------------------------------------
     |  Base DN and campuses (read from the directory)
     * -----------------------------------------------------------------*/

    /** Base DN: ldap.base_dn if set, otherwise people_dn without its first RDN. */
    public function baseDn(): string
    {
        $base = (string) config('ldap.base_dn');
        if ($base !== '') {
            return $base;
        }
        return (string) preg_replace('/^[^,]+,\s*/', '', (string) config('ldap.people_dn'));
    }

    /** Container that holds one OU per campus. */
    public function campusesDn(): string
    {
        return (string) (config('ldap.campuses_dn') ?: 'ou=campuses,' . $this->baseDn());
    }

    /**
     * Campuses = the organizationalUnit entries directly under campusesDn().
     * Same shape the old config had, so views keep working:
     * key (lower-case ou name) => ['label' => 'Ekwendeni', 'dn' => 'ou=ekwendeni,ou=campuses,...']
     *
     * @return array<string,array{label:string,dn:string}> sorted by label
     */
    public function campuses(): array
    {
        if ($this->campusCache !== null) {
            return $this->campusCache;
        }

        $this->ensureConnected();

        $res = @ldap_list($this->conn, $this->campusesDn(), '(objectClass=organizationalUnit)', ['ou']);
        if ($res === false) {
            if (ldap_errno($this->conn) === 32) {
                throw new RuntimeException(
                    'Campuses container not found: ' . $this->campusesDn() .
                    '. Create it, or set ldap.campuses_dn.'
                );
            }
            throw new RuntimeException('Could not read campuses: ' . ldap_error($this->conn));
        }

        $entries  = ldap_get_entries($this->conn, $res);
        $campuses = [];
        for ($i = 0; $i < $entries['count']; $i++) {
            $ou = $entries[$i]['ou'][0] ?? '';
            if ($ou === '') {
                continue;
            }
            $campuses[strtolower($ou)] = [
                'label' => ucwords(str_replace(['-', '_'], ' ', $ou)) . (string) config('ldap.campus_label_suffix', ''),
                'dn'    => $entries[$i]['dn'],
            ];
        }
        uasort($campuses, fn ($a, $b) => strcasecmp($a['label'], $b['label']));

        return $this->campusCache = $campuses;
    }

    public function forgetCampuses(): void
    {
        $this->campusCache = null;
    }

    /* ------------------------------------------------------------------
     |  User types
     * -----------------------------------------------------------------*/

    /** @return array{label:string,affiliations:list<string>,entitlements:list<string>,required:list<string>} */
    private function typeConfig(string $type): array
    {
        $t = config("ldap.user_types.{$type}");
        if (! $t) {
            throw new RuntimeException("Unknown user type: {$type}");
        }
        return $t;
    }

    private function typeFromAffiliations(array $affiliations): ?string
    {
        $have = array_map('strtolower', $affiliations);

        foreach ((array) config('ldap.user_types') as $key => $t) {
            if (in_array(strtolower($t['affiliations'][0]), $have, true)) {
                return $key;
            }
        }
        return null;
    }

    /* ------------------------------------------------------------------
     |  Create / update (used by bulk upload and the single-user form)
     * -----------------------------------------------------------------*/

    /**
     * Create the user, or update it if the uid already exists.
     * An existing account of a DIFFERENT type is never overwritten.
     *
     * @param array{uid:string,givenName:string,sn:string,mail:string,password:string} $u
     * @return string 'created' | 'updated'
     */
    public function upsertUser(array $u, string $campusDn, string $type = 'student'): string
    {
        $this->ensureConnected();

        $t  = $this->typeConfig($type);
        $dn = $this->dnFor($u['uid']);
        $cn = trim($u['givenName'] . ' ' . $u['sn']);

        $entitlements = array_values($t['entitlements'] ?: (array) config('ldap.entitlements', []));

        $attrs = [
            'sn'                   => $u['sn'],
            'givenName'            => $u['givenName'],
            'cn'                   => $cn,
            'displayName'          => $cn,
            'preferredLanguage'    => 'en',
            'userPassword'         => self::ssha($u['password']),
            'mail'                 => $u['mail'],
            'eduPersonOrgUnitDN'   => $campusDn,
            'eduPersonAffiliation' => array_values($t['affiliations']),
        ];
        if ($entitlements) {
            $attrs['eduPersonEntitlement'] = $entitlements;
        }

        $existing = $this->findStudent($u['uid']);

        if ($existing) {
            if ($existing['type'] !== null && $existing['type'] !== $type) {
                throw new RuntimeException(
                    "uid '{$u['uid']}' already exists as {$existing['type']}, not {$type}."
                );
            }
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
     * List users under the people container (uses paged results so servers
     * with a size limit still work).
     *
     * @param string      $q        free text: matches uid, name or e-mail
     * @param string|null $campusDn only users of this campus
     * @param string|null $type     only users of this type (key of ldap.user_types)
     * @return list<array<string,mixed>> sorted by uid
     */
    public function searchStudents(string $q = '', ?string $campusDn = null, ?string $type = null): array
    {
        $this->ensureConnected();

        $filter = '(objectClass=inetOrgPerson)';

        if ($type) {
            $primary = $this->typeConfig($type)['affiliations'][0];
            $p       = ldap_escape($primary, '', LDAP_ESCAPE_FILTER);
            $filter  = "(&{$filter}(eduPersonAffiliation={$p}))";
        }

        if ($q !== '') {
            $e      = ldap_escape($q, '', LDAP_ESCAPE_FILTER);
            $filter = "(&{$filter}(|(uid=*{$e}*)(cn=*{$e}*)(mail=*{$e}*)))";
        }

        $attrs  = ['uid', 'givenName', 'sn', 'cn', 'mail', 'eduPersonOrgUnitDN', 'eduPersonAffiliation'];
        $max    = (int) config('ldap.max_list');
        $users  = [];
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
     |  Edit / reset password / delete
     * -----------------------------------------------------------------*/

    /**
     * Change name, e-mail and campus, and optionally the user type.
     * The username (uid) cannot be changed.
     *
     * @param array{givenName:string,sn:string,mail:string} $c
     * @param string|null $type new type key (ldap.user_types) or null to keep the current one.
     *                          Changing it replaces eduPersonAffiliation with the new type's values.
     */
    public function updateStudent(string $uid, array $c, string $campusDn, ?string $type = null): void
    {
        $this->ensureConnected();

        $cn = trim($c['givenName'] . ' ' . $c['sn']);

        $attrs = [
            'givenName'          => $c['givenName'],
            'sn'                 => $c['sn'],
            'cn'                 => $cn,
            'displayName'        => $cn,
            'mail'               => $c['mail'],
            'eduPersonOrgUnitDN' => $campusDn,
        ];

        if ($type !== null) {
            $t = $this->typeConfig($type);
            $attrs['eduPersonAffiliation'] = array_values($t['affiliations']);
            if ($t['entitlements']) {
                $attrs['eduPersonEntitlement'] = array_values($t['entitlements']);
            }
        }

        $ok = @ldap_modify($this->conn, $this->dnFor($uid), $attrs);

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
            // 32 = No such object
            if (ldap_errno($this->conn) === 32) {
                throw new RuntimeException('User not found in LDAP.');
            }
            throw new RuntimeException('Delete failed: ' . ldap_error($this->conn));
        }
    }

    /* ------------------------------------------------------------------
     |  Counting
     * -----------------------------------------------------------------*/

    /**
     * Count users in a single paged pass.
     *
     * @return array{total:int, byCampus:array<string,int>, byType:array<string,int>}
     *         byCampus is keyed by normalised campus DN; byType by user-type key
     *         (users that match no configured type are counted under 'other').
     */
    public function countStudents(): array
    {
        $this->ensureConnected();

        $norm     = fn (string $s) => strtolower(preg_replace('/\s*,\s*/', ',', trim($s)));
        $total    = 0;
        $byCampus = [];
        $byType   = [];
        $cookie   = '';

        do {
            $res = @ldap_search(
                $this->conn, config('ldap.people_dn'), '(objectClass=inetOrgPerson)',
                ['eduPersonOrgUnitDN', 'eduPersonAffiliation'], 0, 0, 0, LDAP_DEREF_NEVER,
                [['oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => ['size' => (int) config('ldap.page_size'), 'cookie' => $cookie]]]
            );
            if ($res === false) {
                throw new RuntimeException('LDAP count failed: ' . ldap_error($this->conn));
            }

            ldap_parse_result($this->conn, $res, $code, $matched, $message, $referrals, $controls);

            $entries = ldap_get_entries($this->conn, $res);
            for ($i = 0; $i < $entries['count']; $i++) {
                $total++;

                $dn            = $norm($entries[$i]['edupersonorgunitdn'][0] ?? '');
                $byCampus[$dn] = ($byCampus[$dn] ?? 0) + 1;

                $type          = $this->typeFromAffiliations($this->affiliationsOf($entries[$i])) ?? 'other';
                $byType[$type] = ($byType[$type] ?? 0) + 1;
            }

            $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'] ?? '';
        } while ($cookie !== '');

        return ['total' => $total, 'byCampus' => $byCampus, 'byType' => $byType];
    }

    /* ------------------------------------------------------------------
     |  Helpers
     * -----------------------------------------------------------------*/

    /** @return list<string> */
    private function affiliationsOf(array $e): array
    {
        $aff = [];
        for ($i = 0; $i < ($e['edupersonaffiliation']['count'] ?? 0); $i++) {
            $aff[] = $e['edupersonaffiliation'][$i];
        }
        return $aff;
    }

    private function entryToArray(array $e): array
    {
        $get = fn (string $k) => $e[$k][0] ?? '';
        $aff = $this->affiliationsOf($e);

        return [
            'uid'          => $get('uid'),
            'givenName'    => $get('givenname'),
            'sn'           => $get('sn'),
            'name'         => $get('cn'),
            'mail'         => $get('mail'),
            'campusDn'     => $get('edupersonorgunitdn'),
            'affiliations' => $aff,
            'affiliation'  => $aff[0] ?? '',   // kept for existing views
            'type'         => $this->typeFromAffiliations($aff),
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
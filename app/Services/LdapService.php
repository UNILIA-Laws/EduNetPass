<?php

namespace App\Services;

use RuntimeException;

/**
 * Thin wrapper around PHP's ldap_* functions. PHP port of the old Python script.
 */
class LdapService
{
    /** @var \LDAP\Connection|null */
    private $conn = null;

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

    /**
     * Create the user, or update it if the uid already exists.
     *
     * @param array{uid:string,givenName:string,sn:string,mail:string,password:string} $u
     * @return string 'created' | 'updated'
     */
    public function upsertStudent(array $u, string $campusDn): string
    {
        if (! $this->conn) {
            $this->connect();
        }

        $dn = 'uid=' . ldap_escape($u['uid'], '', LDAP_ESCAPE_DN) . ',' . config('ldap.people_dn');
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

    /** Salted SHA-1 ({SSHA}), same format as the Python hash_password(). */
    public static function ssha(string $plain): string
    {
        $salt = random_bytes(4);
        return '{SSHA}' . base64_encode(sha1($plain . $salt, true) . $salt);
    }
}

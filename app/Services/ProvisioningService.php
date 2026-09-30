<?php

namespace App\Services;

use App\Mail\StudentCredentialsMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * For every student: 1) create/update in LDAP, 2) e-mail the credentials.
 * The e-mail is only sent if LDAP succeeded.
 */
class ProvisioningService
{
    public function __construct(private LdapService $ldap)
    {
    }

    /**
     * @param list<array<string,string>> $rows
     * @return list<array<string,mixed>> one result per row
     */
    public function provision(array $rows, string $campusKey): array
    {
        $campusDn = config("ldap.campuses.{$campusKey}.dn");
        if (! $campusDn) {
            throw new \InvalidArgumentException('Unknown campus.');
        }

        set_time_limit(0);

        $results = [];
        $seen = [];

        try {
            $this->ldap->connect(); // one connection for the whole batch

            foreach ($rows as $i => $row) {
                $result = $this->provisionOne($row, $campusDn, $seen);
                $result['line'] = $i + 2; // +2 = header row + 1-based
                $results[] = $result;
            }
        } finally {
            $this->ldap->disconnect();
        }

        return $results;
    }

    private function provisionOne(array $row, string $campusDn, array &$seen): array
    {
        $r = [
            'uid'   => $row['uid'] ?? '',
            'name'  => trim(($row['givenName'] ?? '') . ' ' . ($row['sn'] ?? '')),
            'mail'  => $row['mail'] ?? '',
            'ldap'  => 'skipped',
            'email' => 'skipped',
            'error' => null,
        ];

        // ---- validate -------------------------------------------------
        if ($error = $this->validate($row)) {
            $r['error'] = $error;
            return $r;
        }
        if (isset($seen[strtolower($row['uid'])])) {
            $r['error'] = 'Duplicate uid in this file.';
            return $r;
        }
        $seen[strtolower($row['uid'])] = true;

        $generated = ($row['userPassword'] ?? '') === '';
        $password  = $generated ? self::generatePassword() : $row['userPassword'];

        // ---- LDAP -----------------------------------------------------
        try {
            $r['ldap'] = $this->ldap->upsertStudent([
                'uid'       => $row['uid'],
                'givenName' => $row['givenName'],
                'sn'        => $row['sn'],
                'mail'      => $row['mail'],
                'password'  => $password,
            ], $campusDn);
        } catch (Throwable $e) {
            $r['ldap']  = 'failed';
            $r['error'] = $e->getMessage();
            Log::warning('eduroam.provision ldap failed', ['uid' => $row['uid'], 'error' => $e->getMessage()]);
            return $r;
        }

        // ---- E-mail ---------------------------------------------------
        try {
            Mail::to($row['mail'])->send(new StudentCredentialsMail([
                'givenName'    => $row['givenName'],
                'sn'           => $row['sn'],
                'Reg'          => $row['Reg'] ?? '',
                'uid'          => $row['uid'],
                'userPassword' => $password,
            ]));
            $r['email'] = 'sent';
        } catch (Throwable $e) {
            $r['email'] = 'failed';
            $r['error'] = 'Account saved, but e-mail failed: ' . $e->getMessage();
            // Otherwise a generated password would be lost. Shown once, to the logged-in admin only.
            $r['password'] = $password;
        }

        Log::info('eduroam.provision', ['uid' => $row['uid'], 'ldap' => $r['ldap'], 'email' => $r['email']]);

        return $r;
    }

    private function validate(array $row): ?string
    {
        foreach (['givenName', 'sn', 'uid', 'mail'] as $f) {
            if (($row[$f] ?? '') === '') {
                return "Missing {$f}.";
            }
        }
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/', $row['uid'])) {
            return 'uid may only contain letters, numbers, dot, dash and underscore.';
        }
        if (! filter_var($row['mail'], FILTER_VALIDATE_EMAIL)) {
            return 'Invalid e-mail address.';
        }
        return null;
    }

    public static function generatePassword(int $length = 10): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no look-alikes (0/O, 1/l/I)
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }
}

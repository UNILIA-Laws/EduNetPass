<?php

namespace App\Services;

use RuntimeException;

/**
 * Groups (groupOfNames) and organizational units, built on top of LdapService.
 * Relies on LdapService::$conn, ensureConnected(), dnFor(), baseDn(), campuses() and campusesDn().
 */
class LdapDirectoryService extends LdapService
{
    /* ------------------------------------------------------------------
     |  Helpers
     * -----------------------------------------------------------------*/

    private function norm(string $dn): string
    {
        return strtolower(preg_replace('/\s*,\s*/', ',', trim($dn)));
    }

    private function groupsDn(): string
    {
        return (string) (config('ldap.groups_dn') ?: 'ou=groups,' . $this->baseDn());
    }

    private function groupClass(): string
    {
        return (string) config('ldap.group_class', 'groupOfNames');
    }

    private function groupDn(string $cn): string
    {
        return 'cn=' . ldap_escape($cn, '', LDAP_ESCAPE_DN) . ',' . $this->groupsDn();
    }

    /** groupOfNames requires at least one member; this DN fills the gap for empty groups. */
    private function placeholder(): string
    {
        return (string) (config('ldap.group_placeholder') ?: config('ldap.bind_dn'));
    }

    private function isPlaceholder(string $dn): bool
    {
        return $this->norm($dn) === $this->norm($this->placeholder());
    }

    /** @return list<string> */
    private function values(array $entry, string $attr): array
    {
        $out = [];
        for ($i = 0; $i < ($entry[$attr]['count'] ?? 0); $i++) {
            $out[] = $entry[$attr][$i];
        }
        return $out;
    }

    private function groupToArray(array $e): array
    {
        $members = array_values(array_filter(
            $this->values($e, 'member'),
            fn ($dn) => ! $this->isPlaceholder($dn)
        ));
        sort($members, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'cn'          => $e['cn'][0] ?? '',
            'description' => $e['description'][0] ?? '',
            'dn'          => $e['dn'] ?? '',
            'members'     => $members,
            'count'       => count($members),
        ];
    }

    /** Extract the uid from a member DN such as "uid=jdoe,ou=people,..." */
    public static function uidFromDn(string $dn): string
    {
        return preg_match('/^uid=([^,]+)/i', $dn, $m) ? stripslashes($m[1]) : $dn;
    }

    /* ------------------------------------------------------------------
     |  Groups
     * -----------------------------------------------------------------*/

    /** @return list<array<string,mixed>> sorted by cn */
    public function listGroups(string $q = ''): array
    {
        $this->ensureConnected();

        $filter = '(objectClass=' . $this->groupClass() . ')';
        if ($q !== '') {
            $e      = ldap_escape($q, '', LDAP_ESCAPE_FILTER);
            $filter = "(&{$filter}(|(cn=*{$e}*)(description=*{$e}*)))";
        }

        $res = @ldap_search($this->conn, $this->groupsDn(), $filter, ['cn', 'description', 'member']);
        if ($res === false) {
            if (ldap_errno($this->conn) === 32) {
                throw new RuntimeException('Groups container not found: ' . $this->groupsDn() . '. Create it first (see INSTALL.md).');
            }
            throw new RuntimeException('LDAP group search failed: ' . ldap_error($this->conn));
        }

        $entries = ldap_get_entries($this->conn, $res);
        $groups  = [];
        for ($i = 0; $i < $entries['count']; $i++) {
            $groups[] = $this->groupToArray($entries[$i]);
        }
        usort($groups, fn ($a, $b) => strcasecmp($a['cn'], $b['cn']));

        return $groups;
    }

    /** @return array<string,mixed>|null */
    public function findGroup(string $cn): ?array
    {
        $this->ensureConnected();

        $res = @ldap_read($this->conn, $this->groupDn($cn), '(objectClass=*)', ['cn', 'description', 'member']);
        if ($res === false) {
            if (ldap_errno($this->conn) === 32) {
                return null;
            }
            throw new RuntimeException('LDAP group lookup failed: ' . ldap_error($this->conn));
        }

        $entries = ldap_get_entries($this->conn, $res);
        return $entries['count'] > 0 ? $this->groupToArray($entries[0]) : null;
    }

    /** @param list<string> $uids initial members (may be empty) */
    public function createGroup(string $cn, string $description = '', array $uids = []): void
    {
        $this->ensureConnected();

        $members = array_map(fn ($uid) => $this->dnFor($uid), array_values(array_unique($uids)));

        $attrs = [
            'objectClass' => ['top', $this->groupClass()],
            'cn'          => $cn,
            'member'      => $members ?: [$this->placeholder()],
        ];
        if ($description !== '') {
            $attrs['description'] = $description;
        }

        if (! @ldap_add($this->conn, $this->groupDn($cn), $attrs)) {
            if (ldap_errno($this->conn) === 68) {
                throw new RuntimeException("Group '{$cn}' already exists.");
            }
            throw new RuntimeException('Could not create group: ' . ldap_error($this->conn));
        }
    }

    public function addMember(string $cn, string $uid): void
    {
        $this->ensureConnected();

        $dn = $this->groupDn($cn);

        if (! @ldap_mod_add($this->conn, $dn, ['member' => [$this->dnFor($uid)]])) {
            if (ldap_errno($this->conn) === 20) { // type or value exists
                throw new RuntimeException("{$uid} is already a member of this group.");
            }
            throw new RuntimeException('Could not add member: ' . ldap_error($this->conn));
        }

        // A real member exists now, drop the placeholder if it was there.
        @ldap_mod_del($this->conn, $dn, ['member' => [$this->placeholder()]]);
    }

    public function removeMember(string $cn, string $uid): void
    {
        $this->ensureConnected();

        $group = $this->findGroup($cn);
        if (! $group) {
            throw new RuntimeException('Group not found.');
        }

        $userDn = $this->dnFor($uid);
        $isMember = false;
        foreach ($group['members'] as $m) {
            if ($this->norm($m) === $this->norm($userDn)) {
                $isMember = true;
                break;
            }
        }
        if (! $isMember) {
            throw new RuntimeException("{$uid} is not a member of this group.");
        }

        $dn = $this->groupDn($cn);

        // Last real member: groupOfNames needs >= 1 member, so swap in the placeholder.
        $ok = $group['count'] <= 1
            ? @ldap_mod_replace($this->conn, $dn, ['member' => [$this->placeholder()]])
            : @ldap_mod_del($this->conn, $dn, ['member' => [$userDn]]);

        if (! $ok) {
            throw new RuntimeException('Could not remove member: ' . ldap_error($this->conn));
        }
    }

    public function deleteGroup(string $cn): void
    {
        $this->ensureConnected();

        if (! @ldap_delete($this->conn, $this->groupDn($cn))) {
            if (ldap_errno($this->conn) === 32) {
                throw new RuntimeException('Group not found.');
            }
            throw new RuntimeException('Could not delete group: ' . ldap_error($this->conn));
        }
    }

    /* ------------------------------------------------------------------
     |  Organizational units
     * -----------------------------------------------------------------*/

    /**
     * DNs that must never be deleted from the UI: people, groups, the base,
     * the campuses container and every campus OU currently in the directory.
     *
     * @return list<string>
     */
    private function protectedDns(): array
    {
        $dns = [config('ldap.people_dn'), $this->groupsDn(), $this->baseDn(), $this->campusesDn()];
        foreach ($this->campuses() as $c) {
            $dns[] = $c['dn'];
        }
        return array_values(array_filter(array_map(fn ($d) => $this->norm((string) $d), $dns)));
    }

    /** @return list<string> RDN parts, leaf first */
    private function rdns(string $dn): array
    {
        $parts = ldap_explode_dn($dn, 0) ?: [];
        unset($parts['count']);
        return array_values($parts);
    }

    /**
     * All organizationalUnit entries under the base DN, parents before children.
     *
     * @return list<array{dn:string,name:string,description:string,depth:int,locked:bool,campus:bool}>
     */
    public function listOus(): array
    {
        $this->ensureConnected();

        $base = $this->baseDn();
        $res  = @ldap_search($this->conn, $base, '(objectClass=organizationalUnit)', ['ou', 'description']);
        if ($res === false) {
            throw new RuntimeException('LDAP OU search failed: ' . ldap_error($this->conn));
        }

        $baseDepth = count($this->rdns($base));
        $entries   = ldap_get_entries($this->conn, $res);

        // Campuses come from the directory itself (children of the campuses container)
        $this->forgetCampuses();
        $campusDns = array_map(fn ($c) => $this->norm($c['dn']), $this->campuses());
        $locked    = $this->protectedDns();

        $ous = [];
        for ($i = 0; $i < $entries['count']; $i++) {
            $dn    = $entries[$i]['dn'];
            $parts = $this->rdns($dn);

            $ous[] = [
                'dn'          => $dn,
                'name'        => $entries[$i]['ou'][0] ?? '',
                'description' => $entries[$i]['description'][0] ?? '',
                'depth'       => max(0, count($parts) - $baseDepth - 1),
                'locked'      => in_array($this->norm($dn), $locked, true),
                'campus'      => in_array($this->norm($dn), $campusDns, true),
                '_sort'       => strtolower(implode(',', array_reverse($parts))),
            ];
        }

        usort($ous, fn ($a, $b) => strcmp($a['_sort'], $b['_sort']));
        foreach ($ous as &$o) {
            unset($o['_sort']);
        }

        return $ous;
    }

    public function createOu(string $name, string $parentDn, string $description = ''): void
    {
        $this->ensureConnected();

        $attrs = [
            'objectClass' => ['top', 'organizationalUnit'],
            'ou'          => $name,
        ];
        if ($description !== '') {
            $attrs['description'] = $description;
        }

        $dn = 'ou=' . ldap_escape($name, '', LDAP_ESCAPE_DN) . ',' . $parentDn;

        if (! @ldap_add($this->conn, $dn, $attrs)) {
            if (ldap_errno($this->conn) === 68) {
                throw new RuntimeException("An OU named '{$name}' already exists there.");
            }
            throw new RuntimeException('Could not create OU: ' . ldap_error($this->conn));
        }

        $this->forgetCampuses();
    }

    public function deleteOu(string $dn): void
    {
        $this->ensureConnected();

        if (in_array($this->norm($dn), $this->protectedDns(), true)) {
            throw new RuntimeException('This OU is used by the application (people, groups, campuses or a campus) and cannot be deleted here.');
        }

        if (! @ldap_delete($this->conn, $dn)) {
            $code = ldap_errno($this->conn);
            if ($code === 66) { // not allowed on non-leaf
                throw new RuntimeException('The OU is not empty. Move or delete its entries first.');
            }
            if ($code === 32) {
                throw new RuntimeException('OU not found.');
            }
            throw new RuntimeException('Could not delete OU: ' . ldap_error($this->conn));
        }

        $this->forgetCampuses();
    }

    /* ------------------------------------------------------------------
     |  Campuses (OUs directly under the campuses container)
     * -----------------------------------------------------------------*/

    /** Create the campuses container itself if it does not exist yet. */
    private function ensureCampusesContainer(): void
    {
        $dn  = $this->campusesDn();
        $res = @ldap_read($this->conn, $dn, '(objectClass=*)', ['ou']);
        if ($res !== false) {
            return;
        }
        if (ldap_errno($this->conn) !== 32) {
            throw new RuntimeException('Could not check the campuses container: ' . ldap_error($this->conn));
        }
        if (! preg_match('/^ou=([^,]+),\s*(.+)$/i', $dn, $m)) {
            throw new RuntimeException("Cannot create the campuses container from '{$dn}'.");
        }
        $this->createOu(stripslashes($m[1]), $m[2]);
    }

    /** Create a campus. The name becomes the OU name (lower-case) and the campus key. */
    public function createCampus(string $name, string $description = ''): void
    {
        $this->ensureConnected();
        $this->ensureCampusesContainer();
        $this->createOu(strtolower($name), $this->campusesDn(), $description);
    }

    /** @return array<string,int> campus key => number of users assigned to it */
    public function campusUserCounts(): array
    {
        $byDn = $this->countStudents()['byCampus'];
        $out  = [];
        foreach ($this->campuses() as $key => $c) {
            $out[$key] = $byDn[$this->norm($c['dn'])] ?? 0;
        }
        return $out;
    }

    /** Only the description can change; renaming would change the DN every user stores. */
    public function updateCampusDescription(string $key, string $description): void
    {
        $this->ensureConnected();

        $campus = $this->campuses()[$key] ?? null;
        if (! $campus) {
            throw new RuntimeException('Campus not found.');
        }

        // An empty array removes the attribute
        $value = $description !== '' ? [$description] : [];
        if (! @ldap_mod_replace($this->conn, $campus['dn'], ['description' => $value])) {
            throw new RuntimeException('Could not update the campus: ' . ldap_error($this->conn));
        }

        $this->forgetCampuses();
    }

    /** Delete a campus, but only if no user is assigned to it. */
    public function deleteCampus(string $key): void
    {
        $this->ensureConnected();

        $campus = $this->campuses()[$key] ?? null;
        if (! $campus) {
            throw new RuntimeException('Campus not found.');
        }

        $users = $this->campusUserCounts()[$key] ?? 0;
        if ($users > 0) {
            throw new RuntimeException("{$users} user(s) still belong to this campus. Move them to another campus first.");
        }

        if (! @ldap_delete($this->conn, $campus['dn'])) {
            if (ldap_errno($this->conn) === 66) { // not allowed on non-leaf
                throw new RuntimeException('The campus OU contains other entries. Remove them first.');
            }
            throw new RuntimeException('Could not delete the campus: ' . ldap_error($this->conn));
        }

        $this->forgetCampuses();
    }
}
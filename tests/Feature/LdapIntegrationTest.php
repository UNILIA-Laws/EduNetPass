<?php

namespace Tests\Feature;

use App\Services\LdapService;
use Tests\TestCase;

/**
 * Runs LdapService against a REAL (throw-away) LDAP server.
 * Skipped unless LDAP_TEST_URI is set, e.g.
 *   LDAP_TEST_URI=ldap://127.0.0.1:389 LDAP_TEST_PASSWORD=secret php artisan test --filter=LdapIntegration
 * Use a test server with the eduPerson schema, never production.
 */
class LdapIntegrationTest extends TestCase
{
    private LdapService $ldap;
    private string $campus;
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! getenv('LDAP_TEST_URI')) {
            $this->markTestSkipped('LDAP_TEST_URI not set.');
        }
        config([
            'ldap.uri'           => getenv('LDAP_TEST_URI'),
            'ldap.bind_password' => getenv('LDAP_TEST_PASSWORD'),
        ]);

        $this->ldap   = new LdapService();
        $this->campus = config('ldap.campuses.laws.dn');
    }

    protected function tearDown(): void
    {
        if (isset($this->ldap) && $this->created) {
            $c = ldap_connect(config('ldap.uri'));
            ldap_set_option($c, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_bind($c, config('ldap.bind_dn'), config('ldap.bind_password'));
            foreach ($this->created as $uid) {
                @ldap_delete($c, "uid={$uid}," . config('ldap.people_dn'));
            }
        }
        parent::tearDown();
    }

    private function make(string $uid, string $given = 'Test', string $sn = 'User', string $pw = 'OldPass123'): void
    {
        $this->created[] = $uid;
        $this->assertSame('created', $this->ldap->upsertStudent(
            ['uid' => $uid, 'givenName' => $given, 'sn' => $sn, 'mail' => "$uid@test.mw", 'password' => $pw], $this->campus));
    }

    private function canBind(string $uid, string $pw): bool
    {
        $c = ldap_connect(config('ldap.uri'));
        ldap_set_option($c, LDAP_OPT_PROTOCOL_VERSION, 3);
        return @ldap_bind($c, "uid={$uid}," . config('ldap.people_dn'), $pw);
    }

    public function test_create_then_update_existing(): void
    {
        $this->make('it-user1');
        $this->assertSame('updated', $this->ldap->upsertStudent(
            ['uid' => 'it-user1', 'givenName' => 'New', 'sn' => 'Name', 'mail' => 'n@test.mw', 'password' => 'Other12345'], $this->campus));
        $this->assertSame('New Name', $this->ldap->findStudent('it-user1')['name']);
    }

    public function test_find_and_missing_user(): void
    {
        $this->make('it-user2', 'Grace', 'Hopper');
        $u = $this->ldap->findStudent('it-user2');

        $this->assertSame('Grace', $u['givenName']);
        $this->assertSame('it-user2@test.mw', $u['mail']);
        $this->assertSame('student', $u['affiliation']);
        $this->assertNull($this->ldap->findStudent('does-not-exist'));
    }

    public function test_search_and_campus_filter_and_filter_injection_is_harmless(): void
    {
        $this->make('it-alpha', 'Alpha', 'Zed');
        $this->make('it-beta', 'Beta', 'Zed');

        $uids = fn (array $r) => array_column($r, 'uid');

        $this->assertSame(['it-alpha'], $uids($this->ldap->searchStudents('alpha')));
        $this->assertEqualsCanonicalizing(['it-alpha', 'it-beta'], $uids($this->ldap->searchStudents('zed')));
        $this->assertSame([], $this->ldap->searchStudents('zed', 'ou=other,ou=campuses,dc=unilia,dc=ac,dc=mw'));
        $this->assertCount(2, $this->ldap->searchStudents('zed', strtoupper($this->campus))); // DN compare is case-insensitive
        $this->assertSame([], $this->ldap->searchStudents('*)(uid=*'));                       // escaped, matches nothing
    }

    public function test_update_details(): void
    {
        $this->make('it-user3');
        $this->ldap->updateStudent('it-user3', ['givenName' => 'Ada', 'sn' => 'Lovelace', 'mail' => 'ada@test.mw'], $this->campus);

        $u = $this->ldap->findStudent('it-user3');
        $this->assertSame('Ada Lovelace', $u['name']);
        $this->assertSame('ada@test.mw', $u['mail']);
    }

    public function test_reset_password_changes_what_the_user_can_log_in_with(): void
    {
        $this->make('it-user4', pw: 'OldPass123');
        $this->assertTrue($this->canBind('it-user4', 'OldPass123'));

        $this->ldap->setPassword('it-user4', 'BrandNew456');

        $this->assertTrue($this->canBind('it-user4', 'BrandNew456'));
        $this->assertFalse($this->canBind('it-user4', 'OldPass123'));
    }

    public function test_paged_search_returns_more_than_one_page(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->make(sprintf('it-bulk%02d', $i), 'Bulk', 'Person');
        }
        config(['ldap.page_size' => 5]); // force 3 round-trips to the server (5 + 5 + 2)
        $this->assertCount(12, $this->ldap->searchStudents('it-bulk'));
    }
}
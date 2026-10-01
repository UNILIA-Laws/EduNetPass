<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Services\LdapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    private function fakeLdap(): object
    {
        $fake = new class extends LdapService {
            public array $db = [];
            public array $passwords = [];
            public array $updated = [];

            public function connect(): void {}
            public function disconnect(): void {}

            public function searchStudents(string $q = '', ?string $campusDn = null): array
            {
                return array_values(array_filter($this->db, fn ($u) =>
                    ($q === '' || stripos($u['uid'] . $u['name'] . $u['mail'], $q) !== false)));
            }
            public function findStudent(string $uid): ?array { return $this->db[$uid] ?? null; }
            public function updateStudent(string $uid, array $c, string $campusDn): void { $this->updated[$uid] = $c + ['campusDn' => $campusDn]; }
            public function setPassword(string $uid, string $password): void { $this->passwords[$uid] = $password; }
        };

        $campus = config('ldap.campuses.laws.dn');
        foreach ([['beh-1', 'Ann', 'One', 'ann@x.mw'], ['beh-2', 'Bob', 'Two', 'bob@x.mw']] as [$uid, $g, $s, $m]) {
            $fake->db[$uid] = ['uid' => $uid, 'givenName' => $g, 'sn' => $s, 'name' => "$g $s", 'mail' => $m,
                               'campusDn' => $campus, 'affiliation' => 'student'];
        }
        $this->app->instance(LdapService::class, $fake);
        return $fake;
    }

    private function staff(): User
    {
        return User::factory()->create();
    }

    public function test_user_pages_require_login(): void
    {
        $this->get('/users')->assertRedirect('/login');
        $this->get('/users/beh-1/edit')->assertRedirect('/login');
        $this->post('/users/beh-1/reset-password')->assertRedirect('/login');
    }

    public function test_list_and_search(): void
    {
        $this->fakeLdap();

        $this->actingAs($this->staff())->get('/users')
            ->assertOk()->assertSee('beh-1')->assertSee('Bob Two')->assertSee('2 found');

        $this->actingAs($this->staff())->get('/users?q=bob')
            ->assertOk()->assertSee('beh-2')->assertDontSee('beh-1')->assertSee('1 found');
    }

    public function test_list_shows_error_when_ldap_is_down(): void
    {
        $this->app->instance(LdapService::class, new class extends LdapService {
            public function searchStudents(string $q = '', ?string $campusDn = null): array
            {
                throw new \RuntimeException('Could not connect/bind to LDAP');
            }
        });

        $this->actingAs($this->staff())->get('/users')->assertOk()->assertSee('Could not read from LDAP');
    }

    public function test_edit_page_and_update(): void
    {
        $ldap = $this->fakeLdap();

        $this->actingAs($this->staff())->get('/users/beh-1/edit')->assertOk()->assertSee('ann@x.mw');

        $this->put('/users/beh-1', ['givenName' => 'Anne', 'sn' => 'One', 'mail' => 'anne@x.mw', 'campus' => 'laws'])
            ->assertRedirect('/users/beh-1/edit')->assertSessionHasNoErrors();

        $this->assertSame('anne@x.mw', $ldap->updated['beh-1']['mail']);
        $this->assertSame('Anne', $ldap->updated['beh-1']['givenName']);
    }

    public function test_update_validates_input_and_unknown_user_is_404(): void
    {
        $this->fakeLdap();
        $this->actingAs($this->staff());

        $this->put('/users/beh-1', ['givenName' => '', 'sn' => 'x', 'mail' => 'nope', 'campus' => 'laws'])
            ->assertSessionHasErrors(['givenName', 'mail']);
        $this->get('/users/ghost/edit')->assertNotFound();
    }

    public function test_reset_password_generates_one_and_emails_it(): void
    {
        Mail::fake();
        $ldap = $this->fakeLdap();

        $this->actingAs($this->staff())
            ->post('/users/beh-1/reset-password', ['send_email' => '1'])
            ->assertSessionHas('reset.emailed', true);

        $this->assertSame(10, strlen($ldap->passwords['beh-1']));
        Mail::assertSent(PasswordResetMail::class, fn ($m) =>
            $m->hasTo('ann@x.mw') && $m->student['userPassword'] === $ldap->passwords['beh-1']);
    }

    public function test_reset_without_email_shows_password_once(): void
    {
        Mail::fake();
        $ldap = $this->fakeLdap();

        $this->actingAs($this->staff())
            ->post('/users/beh-2/reset-password', ['userPassword' => 'MyNewPass99'])
            ->assertSessionHas('reset.password', 'MyNewPass99');

        $this->assertSame('MyNewPass99', $ldap->passwords['beh-2']);
        Mail::assertNothingSent();

        $this->get('/users')->assertSee('MyNewPass99'); // flashed once on the next page
        $this->get('/users')->assertDontSee('MyNewPass99'); // and gone afterwards
    }

    public function test_reset_rejects_short_password(): void
    {
        $ldap = $this->fakeLdap();
        $this->actingAs($this->staff())
            ->post('/users/beh-1/reset-password', ['userPassword' => 'short'])
            ->assertSessionHasErrors('userPassword');
        $this->assertArrayNotHasKey('beh-1', $ldap->passwords);
    }
}
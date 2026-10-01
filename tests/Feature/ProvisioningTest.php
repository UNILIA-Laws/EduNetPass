<?php

namespace Tests\Feature;

use App\Mail\StudentCredentialsMail;
use App\Models\User;
use App\Services\LdapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private function fakeLdap(): object
    {
        $fake = new class extends LdapService {
            public array $saved = [];
            public function connect(): void {}
            public function disconnect(): void {}
            public function upsertStudent(array $u, string $campusDn): string
            {
                if ($u['uid'] === 'boom') {
                    throw new \RuntimeException('LDAP add failed: Insufficient access');
                }
                $this->saved[] = $u;
                return 'created';
            }
        };
        $this->app->instance(LdapService::class, $fake);
        return $fake;
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->post('/users', [])->assertRedirect('/login');
    }

    public function test_single_user_is_added_to_ldap_and_emailed(): void
    {
        Mail::fake();
        $ldap = $this->fakeLdap();

        $this->actingAs(User::factory()->create())
            ->post('/users', [
                'givenName' => 'John', 'sn' => 'Doe', 'uid' => 'beh-01-001-24',
                'mail' => 'john@unilia.ac.mw', 'campus' => 'laws', 'userPassword' => '',
            ])->assertSessionHasNoErrors();

        $this->assertCount(1, $ldap->saved);
        $this->assertSame(10, strlen($ldap->saved[0]['password'])); // auto-generated
        Mail::assertSent(StudentCredentialsMail::class, fn ($m) =>
            $m->hasTo('john@unilia.ac.mw') && $m->student['userPassword'] === $ldap->saved[0]['password']);
    }

    public function test_bulk_csv_skips_email_when_ldap_fails_and_rejects_bad_rows(): void
    {
        Mail::fake();
        $ldap = $this->fakeLdap();

        $csv = "givenName,sn,Reg,mail,uid,userPassword\n"
             . "Ann,One,R1,ann@x.mw,ann1,secret123\n"
             . "Bad,Ldap,R2,bad@x.mw,boom,secret123\n"
             . "No,Mail,R3,not-an-email,nomail,secret123\n"
             . "Dup,Ann,R4,dup@x.mw,ANN1,secret123\n"
             . "Bad,Uid,R5,u@x.mw,\"a,b)(cn=*\",secret123\n";

        $res = $this->actingAs(User::factory()->create())
            ->post('/upload-csv', [
                'campus'   => 'laws',
                'csv_file' => UploadedFile::fake()->createWithContent('s.csv', $csv),
            ]);

        $res->assertSessionHasNoErrors();
        $r = collect(session('results'));

        $this->assertSame(['created', 'failed', 'skipped', 'skipped', 'skipped'], $r->pluck('ldap')->all());
        $this->assertSame(['sent', 'skipped', 'skipped', 'skipped', 'skipped'], $r->pluck('email')->all());
        Mail::assertSent(StudentCredentialsMail::class, 1);
        $this->assertCount(1, $ldap->saved);
    }

    public function test_missing_columns_show_a_friendly_error(): void
    {
        $this->fakeLdap();
        $this->actingAs(User::factory()->create())
            ->post('/upload-csv', [
                'campus'   => 'laws',
                'csv_file' => UploadedFile::fake()->createWithContent('s.csv', "name,email\nA,b@c.d\n"),
            ])->assertSessionHasErrors('csv_file');
    }

    public function test_dashboard_and_login_pages_render(): void
    {
        // The login view now says "Sign in" (the old "ICT Staff Login" copy is gone).
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in')
            ->assertSee('ICT staff only');

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Single student');
    }

    public function test_staff_can_log_in_and_wrong_password_is_rejected(): void
    {
        // Set the password explicitly so the test doesn't depend on the factory default.
        $u = User::factory()->create([
            'email'    => 'ict@unilia.ac.mw',
            'password' => Hash::make('secret-pass-123'),
        ]);

        $this->post('/login', ['email' => 'ict@unilia.ac.mw', 'password' => 'nope'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'ict@unilia.ac.mw', 'password' => 'secret-pass-123'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($u);
    }

    public function test_ssha_hash_is_verifiable(): void
    {
        $h = LdapService::ssha('pass1234');
        $this->assertStringStartsWith('{SSHA}', $h);
        $raw = base64_decode(substr($h, 6));
        $digest = substr($raw, 0, 20);
        $salt = substr($raw, 20);
        $this->assertSame($digest, sha1('pass1234' . $salt, true));
    }
}
<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Services\LdapService;
use App\Services\ProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Browse users stored in LDAP, edit their details, reset their password.
 */
class UserController extends Controller
{
    public function __construct(private LdapService $ldap)
    {
    }

    public function index(Request $request)
    {
        $q         = trim((string) $request->query('q', ''));
        $campusKey = (string) $request->query('campus', '');
        $campusDn  = config("ldap.campuses.{$campusKey}.dn");

        $users = [];
        $error = null;
        try {
            $users = $this->ldap->searchStudents($q, $campusDn);
        } catch (Throwable $e) {
            $error = $e->getMessage();
            Log::error('eduroam.users list failed', ['error' => $e->getMessage()]);
        }

        $perPage = (int) config('ldap.per_page');
        $page    = LengthAwarePaginator::resolveCurrentPage();

        $paginator = new LengthAwarePaginator(
            array_slice($users, ($page - 1) * $perPage, $perPage),
            count($users),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('users.index', [
            'users'     => $paginator,
            'q'         => $q,
            'campusKey' => $campusKey,
            'campuses'  => config('ldap.campuses'),
            'error'     => $error,
            'capped'    => count($users) >= (int) config('ldap.max_list'),
        ]);
    }

    public function edit(string $uid)
    {
        $user = $this->findOrFail($uid);

        return view('users.edit', [
            'user'      => $user,
            'campuses'  => config('ldap.campuses'),
            'campusKey' => $this->campusKeyFor($user['campusDn']),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $this->findOrFail($uid);

        $data = $request->validate([
            'givenName' => 'required|string|max:100',
            'sn'        => 'required|string|max:100',
            'mail'      => 'required|email|max:255',
            'campus'    => ['required', Rule::in(array_keys(config('ldap.campuses')))],
        ]);

        try {
            $this->ldap->updateStudent($uid, [
                'givenName' => trim($data['givenName']),
                'sn'        => trim($data['sn']),
                'mail'      => trim($data['mail']),
            ], config("ldap.campuses.{$data['campus']}.dn"));
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.user updated', ['uid' => $uid, 'by' => $request->user()?->email]);

        return redirect()->route('users.edit', $uid)->with('status', 'Details saved.');
    }

    public function resetPassword(Request $request, string $uid)
    {
        $user = $this->findOrFail($uid);

        $data = $request->validate([
            'userPassword' => 'nullable|string|min:8|max:100',
            'send_email'   => 'nullable|boolean',
        ]);

        $password = ($data['userPassword'] ?? '') !== '' ? $data['userPassword'] : ProvisioningService::generatePassword();
        $sendMail = $request->boolean('send_email');

        try {
            $this->ldap->setPassword($uid, $password);
        } catch (Throwable $e) {
            return back()->withErrors(['ldap' => $e->getMessage()]);
        }

        $reset = ['uid' => $uid, 'emailed' => false, 'password' => $password, 'error' => null];

        if ($sendMail && $user['mail'] !== '') {
            try {
                Mail::to($user['mail'])->send(new PasswordResetMail([
                    'givenName'    => $user['givenName'],
                    'sn'           => $user['sn'],
                    'uid'          => $uid,
                    'userPassword' => $password,
                ]));
                $reset['emailed']  = true;
                $reset['password'] = null; // delivered by e-mail, no need to show it
            } catch (Throwable $e) {
                $reset['error'] = 'Password was changed, but the e-mail could not be sent: ' . $e->getMessage();
            }
        } elseif ($sendMail) {
            $reset['error'] = 'This user has no e-mail address on file.';
        }

        Log::info('eduroam.user password reset', [
            'uid' => $uid, 'emailed' => $reset['emailed'], 'by' => $request->user()?->email,
        ]);

        return back()->with('reset', $reset);
    }

    private function findOrFail(string $uid): array
    {
        try {
            $user = $this->ldap->findStudent($uid);
        } catch (Throwable $e) {
            abort(503, $e->getMessage());
        }

        abort_if($user === null, 404, 'User not found in LDAP.');

        return $user;
    }

    private function campusKeyFor(string $dn): ?string
    {
        $norm = fn ($s) => strtolower(preg_replace('/\s*,\s*/', ',', trim($s)));
        foreach (config('ldap.campuses') as $key => $c) {
            if ($norm($c['dn']) === $norm($dn)) {
                return $key;
            }
        }
        return null;
    }
}
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
 * Browse users stored in LDAP (all types), edit their details, reset their password.
 * Campuses are read from the directory (OUs under ldap.campuses_dn).
 */
class UserController extends Controller
{
    public function __construct(private LdapService $ldap)
    {
    }

    public function index(Request $request)
    {
        $types     = config('ldap.user_types');
        $q         = trim((string) $request->query('q', ''));
        $campusKey = (string) $request->query('campus', '');

        // Unknown type values in the URL are ignored
        $typeKey = (string) $request->query('type', '');
        if (! isset($types[$typeKey])) {
            $typeKey = '';
        }

        $campuses = [];
        $users    = [];
        $error    = null;
        try {
            $campuses = $this->ldap->campuses();
            $campusDn = $campuses[$campusKey]['dn'] ?? null;

            $users = $this->ldap->searchStudents($q, $campusDn, $typeKey ?: null);
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
            'typeKey'   => $typeKey,
            'campuses'  => $campuses,
            'types'     => $types,
            'error'     => $error,
            'capped'    => count($users) >= (int) config('ldap.max_list'),
        ]);
    }

    public function edit(string $uid)
    {
        $user     = $this->findOrFail($uid);
        $campuses = $this->campusesOrFail();

        return view('users.edit', [
            'user'      => $user,
            'campuses'  => $campuses,
            'types'     => config('ldap.user_types'),
            'campusKey' => $this->campusKeyFor($user['campusDn'], $campuses),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $user     = $this->findOrFail($uid);
        $campuses = $this->campusesOrFail();

        $data = $request->validate([
            'givenName' => 'required|string|max:100',
            'sn'        => 'required|string|max:100',
            'mail'      => 'required|email|max:255',
            'campus'    => ['required', Rule::in(array_keys($campuses))],
            'type'      => ['nullable', Rule::in(array_keys(config('ldap.user_types')))],
        ]);

        // Only touch the affiliation when the type was actually changed
        $newType = (($data['type'] ?? '') !== '' && $data['type'] !== $user['type']) ? $data['type'] : null;

        try {
            $this->ldap->updateStudent($uid, [
                'givenName' => trim($data['givenName']),
                'sn'        => trim($data['sn']),
                'mail'      => trim($data['mail']),
            ], $campuses[$data['campus']]['dn'], $newType);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.user updated', [
            'uid' => $uid, 'type_from' => $user['type'], 'type_to' => $newType, 'by' => $request->user()?->email,
        ]);

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
                    'type'         => $user['type'],
                    'typeLabel'    => $user['type']
                        ? config("ldap.user_types.{$user['type']}.label")
                        : 'User',
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
            'uid' => $uid, 'type' => $user['type'], 'emailed' => $reset['emailed'], 'by' => $request->user()?->email,
        ]);

        return back()->with('reset', $reset);
    }

    public function destroy(Request $request, string $uid)
    {
        $user = $this->findOrFail($uid);

        try {
            $this->ldap->deleteStudent($uid);
        } catch (Throwable $e) {
            return back()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.user deleted', ['uid' => $uid, 'type' => $user['type'], 'by' => $request->user()?->email]);

        return back()->with('status', "User {$uid} deleted.");
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

    /** @return array<string,array{label:string,dn:string}> */
    private function campusesOrFail(): array
    {
        try {
            return $this->ldap->campuses();
        } catch (Throwable $e) {
            abort(503, $e->getMessage());
        }
    }

    private function campusKeyFor(string $dn, array $campuses): ?string
    {
        $norm = fn ($s) => strtolower(preg_replace('/\s*,\s*/', ',', trim($s)));
        foreach ($campuses as $key => $c) {
            if ($norm($c['dn']) === $norm($dn)) {
                return $key;
            }
        }
        return null;
    }
}
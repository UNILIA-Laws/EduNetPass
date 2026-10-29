<?php

namespace App\Http\Controllers;

use App\Services\LdapDirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Browse, create and delete LDAP groups and manage their members.
 */
class GroupController extends Controller
{
    public function __construct(private LdapDirectoryService $ldap)
    {
    }

    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $groups = [];
        $error  = null;

        try {
            $groups = $this->ldap->listGroups($q);
        } catch (Throwable $e) {
            $error = $e->getMessage();
            Log::error('eduroam.groups list failed', ['error' => $e->getMessage()]);
        }

        return view('groups.index', compact('groups', 'q', 'error'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cn'          => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9][A-Za-z0-9 ._-]*$/'],
            'description' => 'nullable|string|max:255',
        ], [
            'cn.regex' => 'Use letters, numbers, spaces, dots, dashes or underscores only.',
        ]);

        try {
            $this->ldap->createGroup(trim($data['cn']), trim((string) ($data['description'] ?? '')));
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.group created', ['cn' => $data['cn'], 'by' => $request->user()?->email]);

        return redirect()->route('groups.show', $data['cn'])->with('status', 'Group created.');
    }

    public function show(string $cn)
    {
        $group = $this->findOrFail($cn);

        $members = array_map(fn ($dn) => [
            'uid'  => LdapDirectoryService::uidFromDn($dn),
            'dn'   => $dn,
            'user' => null,
        ], $group['members']);

        // Resolve display names (a stale member DN just shows its uid).
        foreach ($members as &$m) {
            try {
                $m['user'] = $this->ldap->findStudent($m['uid']);
            } catch (Throwable) {
                // ignore, show uid only
            }
        }
        unset($m);

        return view('groups.show', ['group' => $group, 'members' => $members]);
    }

    public function addMember(Request $request, string $cn)
    {
        $this->findOrFail($cn);

        $data = $request->validate(['uid' => 'required|string|max:100']);
        $uid  = trim($data['uid']);

        try {
            if ($this->ldap->findStudent($uid) === null) {
                return back()->withInput()->withErrors(['uid' => "No LDAP user with uid '{$uid}'."]);
            }
            $this->ldap->addMember($cn, $uid);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.group member added', ['cn' => $cn, 'uid' => $uid, 'by' => $request->user()?->email]);

        return back()->with('status', "{$uid} added.");
    }

    public function removeMember(Request $request, string $cn, string $uid)
    {
        $this->findOrFail($cn);

        try {
            $this->ldap->removeMember($cn, $uid);
        } catch (Throwable $e) {
            return back()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.group member removed', ['cn' => $cn, 'uid' => $uid, 'by' => $request->user()?->email]);

        return back()->with('status', "{$uid} removed.");
    }

    public function destroy(Request $request, string $cn)
    {
        $this->findOrFail($cn);

        try {
            $this->ldap->deleteGroup($cn);
        } catch (Throwable $e) {
            return back()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.group deleted', ['cn' => $cn, 'by' => $request->user()?->email]);

        return redirect()->route('groups.index')->with('status', "Group {$cn} deleted.");
    }

    private function findOrFail(string $cn): array
    {
        try {
            $group = $this->ldap->findGroup($cn);
        } catch (Throwable $e) {
            abort(503, $e->getMessage());
        }

        abort_if($group === null, 404, 'Group not found in LDAP.');

        return $group;
    }
}

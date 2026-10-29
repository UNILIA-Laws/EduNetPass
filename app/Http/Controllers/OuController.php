<?php

namespace App\Http\Controllers;

use App\Services\LdapDirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Browse, create and delete LDAP organizational units.
 */
class OuController extends Controller
{
    public function __construct(private LdapDirectoryService $ldap)
    {
    }

    public function index()
    {
        $ous   = [];
        $error = null;

        try {
            $ous = $this->ldap->listOus();
        } catch (Throwable $e) {
            $error = $e->getMessage();
            Log::error('eduroam.ous list failed', ['error' => $e->getMessage()]);
        }

        return view('ous.index', [
            'ous'     => $ous,
            'baseDn'  => $this->ldap->baseDn(),
            'error'   => $error,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9][A-Za-z0-9 ._-]*$/'],
            'parent'      => 'required|string|max:512',
            'description' => 'nullable|string|max:255',
        ], [
            'name.regex' => 'Use letters, numbers, spaces, dots, dashes or underscores only.',
        ]);

        // The parent must be an OU that really exists under the base DN.
        try {
            $known = array_column($this->ldap->listOus(), 'dn');
            $known[] = $this->ldap->baseDn();
            $norm  = fn ($s) => strtolower(preg_replace('/\s*,\s*/', ',', trim($s)));

            if (! in_array($norm($data['parent']), array_map($norm, $known), true)) {
                return back()->withInput()->withErrors(['parent' => 'Unknown parent OU.']);
            }

            $this->ldap->createOu(trim($data['name']), trim($data['parent']), trim((string) ($data['description'] ?? '')));
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.ou created', ['name' => $data['name'], 'parent' => $data['parent'], 'by' => $request->user()?->email]);

        return back()->with('status', "OU {$data['name']} created.");
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['dn' => 'required|string|max:512']);

        try {
            // Only DNs we actually listed can be deleted.
            $norm  = fn ($s) => strtolower(preg_replace('/\s*,\s*/', ',', trim($s)));
            $known = array_map($norm, array_column($this->ldap->listOus(), 'dn'));
            abort_unless(in_array($norm($data['dn']), $known, true), 404, 'OU not found.');

            $this->ldap->deleteOu($data['dn']);
        } catch (Throwable $e) {
            return back()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.ou deleted', ['dn' => $data['dn'], 'by' => $request->user()?->email]);

        return back()->with('status', 'OU deleted.');
    }
}
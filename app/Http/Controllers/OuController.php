<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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
            Log::error('MyLDAP.ous list failed', ['error' => $e->getMessage()]);
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

        $name   = trim($data['name']);
        $parent = trim($data['parent']);

        // The parent must be an OU that really exists under the base DN.
        try {
            $known = array_column($this->ldap->listOus(), 'dn');
            $known[] = $this->ldap->baseDn();
            $norm  = fn ($s) => strtolower(preg_replace('/\s*,\s*/', ',', trim($s)));

            if (! in_array($norm($parent), array_map($norm, $known), true)) {
                Log::warning('MyLDAP.ou create rejected, unknown parent', [
                    'name' => $name, 'parent' => $parent, 'by' => $request->user()?->email,
                ]);
                ActivityLog::record('ou.create_rejected', $name, ['parent' => $parent]);

                return back()->withInput()->withErrors(['parent' => 'Unknown parent OU.']);
            }

            $this->ldap->createOu($name, $parent, trim((string) ($data['description'] ?? '')));
        } catch (Throwable $e) {
            Log::error('MyLDAP.ou create failed', [
                'name' => $name, 'parent' => $parent, 'error' => $e->getMessage(), 'by' => $request->user()?->email,
            ]);
            ActivityLog::record('ou.create_failed', $name, ['parent' => $parent]);

            return back()->withInput()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('MyLDAP.ou created', ['name' => $name, 'parent' => $parent, 'by' => $request->user()?->email]);
        ActivityLog::record('ou.created', $name, ['parent' => $parent]);

        return back()->with('status', "OU {$name} created.");
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
            Log::error('MyLDAP.ou delete failed', [
                'dn' => $data['dn'], 'error' => $e->getMessage(), 'by' => $request->user()?->email,
            ]);
            ActivityLog::record('ou.delete_failed', $data['dn']);

            return back()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('MyLDAP.ou deleted', ['dn' => $data['dn'], 'by' => $request->user()?->email]);
        ActivityLog::record('ou.deleted', $data['dn']);

        return back()->with('status', 'OU deleted.');
    }
}
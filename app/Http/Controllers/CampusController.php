<?php

namespace App\Http\Controllers;

use App\Services\LdapDirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Campuses are the organizational units directly under ldap.campuses_dn.
 * Nothing is stored in the application database or in config.
 * The {campus} route parameter is the campus key (the lower-case OU name).
 */
class CampusController extends Controller
{
    public function __construct(private LdapDirectoryService $ldap)
    {
    }

    public function index()
    {
        $rows  = [];
        $error = null;

        try {
            $counts = $this->ldap->campusUserCounts();
            foreach ($this->ldap->campuses() as $key => $c) {
                $rows[] = $c + ['key' => $key, 'users' => $counts[$key] ?? 0];
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
            Log::error('eduroam.campuses list failed', ['error' => $error]);
        }

        return view('campuses.index', [
            'campuses'  => $rows,
            'error'     => $error,
            'container' => $this->ldap->campusesDn(),
        ]);
    }

    public function create()
    {
        return view('campuses.create', ['container' => $this->ldap->campusesDn()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'regex:/^[A-Za-z0-9][A-Za-z0-9_-]{0,62}$/'],
            'description' => 'nullable|string|max:255',
        ], [
            'name.regex' => 'Use letters, numbers, dash and underscore only (no spaces).',
        ]);

        $name = strtolower(trim($data['name']));

        try {
            $this->ldap->createCampus($name, trim($data['description'] ?? ''));
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.campus created', ['campus' => $name, 'by' => $request->user()?->email]);

        return redirect()->route('campuses.index')->with('status', "Campus '{$name}' created.");
    }

    public function edit(string $campus)
    {
        $row = $this->findOrFail($campus);

        try {
            $users = $this->ldap->campusUserCounts()[$campus] ?? 0;
        } catch (Throwable $e) {
            $users = null;
        }

        return view('campuses.edit', [
            'campus' => $row + ['key' => $campus],
            'users'  => $users,
        ]);
    }

    public function update(Request $request, string $campus)
    {
        $this->findOrFail($campus);

        $data = $request->validate([
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $this->ldap->updateCampusDescription($campus, trim($data['description'] ?? ''));
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.campus updated', ['campus' => $campus, 'by' => $request->user()?->email]);

        return redirect()->route('campuses.index')->with('status', "Campus '{$campus}' updated.");
    }

    public function destroy(Request $request, string $campus)
    {
        $this->findOrFail($campus);

        try {
            $this->ldap->deleteCampus($campus);
        } catch (Throwable $e) {
            return back()->withErrors(['ldap' => $e->getMessage()]);
        }

        Log::info('eduroam.campus deleted', ['campus' => $campus, 'by' => $request->user()?->email]);

        return redirect()->route('campuses.index')->with('status', "Campus '{$campus}' deleted.");
    }

    /** @return array{label:string,dn:string,description:string} */
    private function findOrFail(string $key): array
    {
        try {
            $campus = $this->ldap->campuses()[$key] ?? null;
        } catch (Throwable $e) {
            abort(503, $e->getMessage());
        }

        abort_if($campus === null, 404, 'Campus not found in LDAP.');

        return $campus;
    }
}
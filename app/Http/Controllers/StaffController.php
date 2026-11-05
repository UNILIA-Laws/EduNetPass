<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    /**
     * Campuses are organizational units in LDAP, not a database table.
     * Returns [list of ['key' => ..., 'label' => ...], error message or null].
     *
     * TODO: replace the body of the try block with the same call that
     * CampusController@index uses to build its $campuses array.
     */
    private function ldapCampuses(): array
    {
        try {
            $rows = app(\App\Services\CampusDirectory::class)->all(); // <- your existing LDAP campus reader

            $list = collect($rows)
                ->map(fn ($c) => ['key' => (string) $c['key'], 'label' => (string) $c['label']])
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();

            return [$list, null];
        } catch (\Throwable $e) {
            Log::warning('MyLDAP.staff could not read campuses from LDAP', ['error' => $e->getMessage()]);
            return [[], $e->getMessage()];
        }
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $staff = User::query()
            ->when($q !== '', fn ($query) => $query->where(
                fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // key => label, so the list can show a campus name instead of the raw OU key
        [$campuses] = $this->ldapCampuses();
        $campusLabels = collect($campuses)->pluck('label', 'key')->all();

        return view('staff.index', ['staff' => $staff, 'q' => $q, 'campusLabels' => $campusLabels]);
    }

    public function create()
    {
        [$campuses, $campusError] = $this->ldapCampuses();

        return view('staff.create', compact('campuses', 'campusError'));
    }

    public function store(Request $request)
    {
        [$campuses, $campusError] = $this->ldapCampuses();

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:100',
            'is_admin' => 'nullable|boolean',
            'campus'   => ['nullable', 'string', 'max:255', Rule::in(array_column($campuses, 'key'))],
        ]);

        $user = new User();
        $user->name     = trim($data['name']);
        $user->email    = strtolower(trim($data['email']));
        $user->password = $data['password'];          // hashed by the model cast
        $user->is_admin = $request->boolean('is_admin');
        $user->campus   = $campusError ? null : ($data['campus'] ?? null);
        $user->email_verified_at = now();
        $user->save();

        Log::info('MyLDAP.staff created', ['email' => $user->email, 'by' => $request->user()?->email]);
        ActivityLog::record('staff.created', $user->email, array_filter([
            'admin'  => $user->is_admin ? 'yes' : null,
            'campus' => $user->campus,
        ]));

        return redirect()->route('staff.index')->with('status', "{$user->name} added.");
    }

    public function edit(User $user)
    {
        [$campuses, $campusError] = $this->ldapCampuses();

        return view('staff.edit', compact('user', 'campuses', 'campusError'));
    }

    public function update(Request $request, User $user)
    {
        [$campuses, $campusError] = $this->ldapCampuses();

        // Allow the value already saved, even if that OU has since been removed from LDAP
        $allowed = array_column($campuses, 'key');
        if ($user->campus) {
            $allowed[] = $user->campus;
        }

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|max:100',
            'is_admin' => 'nullable|boolean',
            'campus'   => ['nullable', 'string', 'max:255', Rule::in($allowed)],
        ]);

        $makeAdmin = $request->boolean('is_admin');

        if ($user->is_admin && ! $makeAdmin) {
            if ($user->is($request->user())) {
                return back()->withInput()->withErrors(['is_admin' => 'You cannot remove your own admin access.']);
            }
            if (User::where('is_admin', true)->count() <= 1) {
                return back()->withInput()->withErrors(['is_admin' => 'There must be at least one administrator.']);
            }
        }

        $user->name    = trim($data['name']);
        $user->email   = strtolower(trim($data['email']));
        $user->is_admin = $makeAdmin;
        // If LDAP could not be read the field is disabled, so keep whatever is stored
        if (! $campusError) {
            $user->campus = $data['campus'] ?? null;
        }
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        // Field names only. Values (and especially passwords) are never written to the log.
        $changed = array_values(array_diff(array_keys($user->getChanges()), ['updated_at', 'remember_token']));
        ActivityLog::record('staff.updated', $user->email, $changed ? ['changed' => $changed] : []);

        Log::info('MyLDAP.staff updated', ['id' => $user->id, 'by' => $request->user()?->email]);

        return redirect()->route('staff.index')->with('status', "{$user->name} updated.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['staff' => 'You cannot delete your own account.']);
        }
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->withErrors(['staff' => 'You cannot delete the last administrator.']);
        }

        $user->delete();

        ActivityLog::record('staff.deleted', $user->email);

        Log::info('MyLDAP.staff deleted', ['email' => $user->email, 'by' => $request->user()?->email]);

        return back()->with('status', "{$user->name} deleted.");
    }
}
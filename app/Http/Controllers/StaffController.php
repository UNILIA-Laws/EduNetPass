<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $staff = User::with('campus')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('staff.index', ['staff' => $staff, 'q' => $q]);
    }

    public function create()
    {
        return view('staff.create', [
            'campuses' => Campus::orderBy('sort_order')->orderBy('label')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|max:255|unique:users,email',
            'password'  => 'required|string|min:8|max:100',
            'is_admin'  => 'nullable|boolean',
            'campus_id' => 'nullable|exists:campuses,id',
        ]);

        $user = new User();
        $user->name      = trim($data['name']);
        $user->email     = strtolower(trim($data['email']));
        $user->password  = $data['password'];          // hashed by the model cast
        $user->is_admin  = $request->boolean('is_admin');
        $user->campus_id = $data['campus_id'] ?? null;
        $user->email_verified_at = now();
        $user->save();

        Log::info('eduroam.staff created', ['email' => $user->email, 'by' => $request->user()?->email]);

        return redirect()->route('staff.index')->with('status', "{$user->name} added.");
    }

    public function edit(User $user)
    {
        return view('staff.edit', [
            'user'     => $user,
            'campuses' => Campus::orderBy('sort_order')->orderBy('label')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'  => 'nullable|string|min:8|max:100',
            'is_admin'  => 'nullable|boolean',
            'campus_id' => 'nullable|exists:campuses,id',
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

        $user->name      = trim($data['name']);
        $user->email     = strtolower(trim($data['email']));
        $user->is_admin  = $makeAdmin;
        $user->campus_id = $data['campus_id'] ?? null;
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        Log::info('eduroam.staff updated', ['id' => $user->id, 'by' => $request->user()?->email]);

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

        Log::info('eduroam.staff deleted', ['email' => $user->email, 'by' => $request->user()?->email]);

        return back()->with('status', "{$user->name} deleted.");
    }
}
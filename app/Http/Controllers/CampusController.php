<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampusController extends Controller
{
    public function index()
    {
        $campuses = Campus::withCount('users')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return view('campuses.index', compact('campuses'));
    }

    public function create()
    {
        return view('campuses.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key'        => ['required', 'alpha_dash', 'max:255', 'unique:campuses,key'],
            'label'      => ['required', 'string', 'max:255', 'unique:campuses,label'],
            'dn'         => ['required', 'string', 'max:255', 'unique:campuses,dn'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active']  = $request->boolean('is_active');

        Campus::create($data);

        return redirect()->route('campuses.index')->with('status', 'Campus created.');
    }

    public function edit(Campus $campus)
    {
        return view('campuses.edit', compact('campus'));
    }

    public function update(Request $request, Campus $campus)
    {
        // "key" is a stable slug, so it is not editable
        $data = $request->validate([
            'label'      => ['required', 'string', 'max:255', Rule::unique('campuses', 'label')->ignore($campus->id)],
            'dn'         => ['required', 'string', 'max:255', Rule::unique('campuses', 'dn')->ignore($campus->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active']  = $request->boolean('is_active');

        $campus->update($data);

        return redirect()->route('campuses.index')->with('status', 'Campus updated.');
    }

    public function destroy(Campus $campus)
    {
        // Users are un-assigned automatically (nullOnDelete in the migration)
        $campus->delete();

        return redirect()->route('campuses.index')->with('status', 'Campus deleted.');
    }
}
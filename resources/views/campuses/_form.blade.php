@php($editing = isset($campus))

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div>
    <label for="key">Key (slug)</label>
    <input id="key" name="key" type="text"
           value="{{ old('key', $campus->key ?? '') }}"
           {{ $editing ? 'disabled' : 'required' }}>
    <small>{{ $editing ? 'The key cannot be changed.' : 'Letters, numbers, dashes and underscores. Cannot be changed later.' }}</small>
</div>

<div>
    <label for="label">Label</label>
    <input id="label" name="label" type="text" required
           value="{{ old('label', $campus->label ?? '') }}">
</div>

<div>
    <label for="dn">eduPersonOrgUnitDN</label>
    <input id="dn" name="dn" type="text" required
           value="{{ old('dn', $campus->dn ?? '') }}"
           placeholder="ou=main,dc=example,dc=edu">
</div>

<div>
    <label for="sort_order">Sort order</label>
    <input id="sort_order" name="sort_order" type="number" min="0"
           value="{{ old('sort_order', $campus->sort_order ?? 0) }}">
</div>

<div>
    <label>
        <input type="checkbox" name="is_active" value="1"
               {{ old('is_active', $campus->is_active ?? true) ? 'checked' : '' }}>
        Active
    </label>
</div>
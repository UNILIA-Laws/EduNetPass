@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit campus: {{ $campus->label }}</h1>

    <form method="POST" action="{{ route('campuses.update', $campus) }}">
        @csrf
        @method('PUT')
        @include('campuses._form')
        <button type="submit">Save changes</button>
        <a href="{{ route('campuses.index') }}">Cancel</a>
    </form>
</div>
@endsection
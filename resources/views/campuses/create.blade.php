@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add campus</h1>

    <form method="POST" action="{{ route('campuses.store') }}">
        @csrf
        @include('campuses._form')
        <button type="submit">Create campus</button>
        <a href="{{ route('campuses.index') }}">Cancel</a>
    </form>
</div>
@endsection
@if(session('status'))
    <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-circle-check me-2"></i>{{ session('status') }}</div>
@endif

@if($errors->has('ldap'))
    <div class="alert alert-danger border-0 shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i>{{ $errors->first('ldap') }}</div>
@endif

@if($reset = session('reset'))
    @if($reset['emailed'])
        <div class="alert alert-success border-0 shadow-sm">
            <i class="fa-solid fa-key me-2"></i>Password for <strong>{{ $reset['uid'] }}</strong> was reset and the new one was e-mailed to the student.
        </div>
    @else
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="fa-solid fa-key me-2"></i>Password for <strong>{{ $reset['uid'] }}</strong> was reset.
            @if($reset['error'])<div class="small mt-1">{{ $reset['error'] }}</div>@endif
            <div class="mt-2">New password (shown only once, give it to the student): <code class="fs-6 user-select-all">{{ $reset['password'] }}</code></div>
        </div>
    @endif
@endif
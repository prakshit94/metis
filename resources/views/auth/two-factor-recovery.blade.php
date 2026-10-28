@extends('layouts.auth')
@section('title', 'Save your recovery codes')
@section('content')
<main class="container py-5" id="main-content" style="max-width: 720px;">
    <div class="card p-4">
        <h1 class="h3">Two-step login is on</h1>
        <p>You have signed in successfully. Save these recovery codes before continuing. Each code works once; they will not be shown again.</p>
        <div class="row g-2 mb-4">@foreach($codes as $code)<div class="col-sm-6"><code class="d-block border rounded p-2 user-select-all">{{ $code }}</code></div>@endforeach</div>
        <p class="small text-muted">Keep them somewhere you can access without your phone or email. You can replace them from Security settings.</p>
        <a href="{{ $continue_url }}" class="btn btn-primary">Continue to application</a>
    </div>
</main>
@endsection

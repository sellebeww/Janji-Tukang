@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

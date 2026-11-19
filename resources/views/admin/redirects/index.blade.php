@extends('admin.content.index')

@section('index-actions')
    <a href="{{ route('admin.redirects.export') }}" class="a-btn a-btn-light">Export CSV</a>
@endsection

@section('above-table')
<details class="a-card mb-5" @if(session('import_errors')) open @endif>
    <summary class="a-card-h cursor-pointer"><span class="a-card-t">Import redirects from CSV</span></summary>
    <form method="POST" action="{{ route('admin.redirects.import') }}" enctype="multipart/form-data" class="a-card-b flex flex-wrap items-end gap-3">
        @csrf
        <div class="flex-1">
            <input type="file" name="csv" accept=".csv,text/csv" class="a-input" required>
            <p class="a-help">Columns: <code>old_url,new_url,status_code</code> (status optional, default 301). Existing old URLs are updated.</p>
        </div>
        <button class="a-btn a-btn-dark">Import</button>
    </form>
    @if(session('import_errors'))
        <ul class="mx-5 mb-5 list-disc rounded-lg bg-amber-50 p-3 pl-8 text-xs text-amber-800">@foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif
</details>
@endsection

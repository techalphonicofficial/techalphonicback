@extends('admin.layouts.app')

@section('title', $role->exists ? 'Role: '.$role->name : 'New role')

@section('content')
<a href="{{ route('admin.roles.index') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-ink">← Roles</a>
@if($role->name === 'Super Admin')
    <p class="a-card a-card-b max-w-xl text-sm text-slate-600">The Super Admin role always has every permission and can't be edited.</p>
@else
<form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="a-card max-w-5xl">
    @csrf @if($role->exists) @method('PUT') @endif
    <div class="a-card-b">
        <x-admin.field name="name" label="Role name" :value="$role->name" required class="max-w-sm" />
        @php $granted = old('permissions', $granted); @endphp
        <div class="mt-6 overflow-x-auto">
            <table class="a-table">
                <thead><tr><th>Content</th>@foreach($actions as $a)<th class="text-center">{{ ucfirst($a) }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach($resources as $key => $label)
                        <tr>
                            <td class="font-medium">{{ $label }}</td>
                            @foreach($actions as $a)
                                <td class="text-center"><input type="checkbox" name="permissions[]" value="{{ $key }}.{{ $a }}" class="a-check" @checked(in_array("$key.$a", $granted, true))></td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="a-label mt-6">Other permissions</p>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($special as $perm => $label)
                <label class="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm"><input type="checkbox" name="permissions[]" value="{{ $perm }}" class="a-check" @checked(in_array($perm, $granted, true))> {{ $label }}</label>
            @endforeach
        </div>
    </div>
    <div class="flex justify-between border-t border-line p-4">
        @if($role->exists && ! in_array($role->name, array_keys(\App\Support\Permissions::defaultRoles()), true))
            <button form="del-role" class="a-btn a-btn-danger" onclick="return confirm('Delete role?')">Delete role</button>
        @else <span></span> @endif
        <button class="a-btn a-btn-primary">Save role</button>
    </div>
</form>
@if($role->exists)<form id="del-role" method="POST" action="{{ route('admin.roles.destroy', $role) }}">@csrf @method('DELETE')</form>@endif
@endif
@endsection

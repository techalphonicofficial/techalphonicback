@extends('admin.layouts.app')

@section('title', $item->exists ? 'Edit user' : 'New user')

@section('content')
<a href="{{ route('admin.users.index') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-ink">← Users</a>
<form method="POST" action="{{ $item->exists ? route('admin.users.update', $item) : route('admin.users.store') }}" class="a-card max-w-2xl">
    @csrf @if($item->exists) @method('PUT') @endif
    <div class="a-card-b grid gap-4 md:grid-cols-2">
        <x-admin.field name="name" label="Name" :value="$item->name" required />
        <x-admin.field type="email" name="email" label="Email" :value="$item->email" required autocomplete="off" />
        <x-admin.field name="phone" label="Phone" :value="$item->phone" />
        <div>
            <p class="a-label">Roles</p>
            @php $current = old('roles', $item->exists ? $item->getRoleNames()->all() : []); @endphp
            <div class="grid gap-1.5">
                @foreach($roles as $r)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="roles[]" value="{{ $r }}" class="a-check" @checked(in_array($r, $current, true)) @disabled($item->is(auth()->user()))> {{ $r }}</label>
                @endforeach
            </div>
            @if($item->is(auth()->user()))<p class="a-help">You can't change your own role.</p>@foreach($current as $r)<input type="hidden" name="roles[]" value="{{ $r }}">@endforeach @endif
            @error('roles')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <x-admin.field type="password" name="password" :label="$item->exists ? 'New password (leave blank to keep)' : 'Password'" autocomplete="new-password" help="Min 10 characters with upper & lower case, a number and a symbol." />
        <x-admin.field type="password" name="password_confirmation" label="Confirm password" autocomplete="new-password" />
        <x-admin.field type="checkbox" name="is_active" label="Account active" :value="$item->is_active ?? true" />
    </div>
    <div class="flex justify-between border-t border-line p-4">
        @if($item->exists && ! $item->is(auth()->user()))
            <button form="del-user" class="a-btn a-btn-danger" onclick="return confirm('Remove this user?')">Delete</button>
        @else <span></span> @endif
        <button class="a-btn a-btn-primary">Save</button>
    </div>
</form>
@if($item->exists)<form id="del-user" method="POST" action="{{ route('admin.users.destroy', $item) }}">@csrf @method('DELETE')</form>@endif
@endsection

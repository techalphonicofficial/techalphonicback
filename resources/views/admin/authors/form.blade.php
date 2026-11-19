@extends('admin.content.form', ['tabs' => ['basic' => 'Author']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="name" label="Name" :value="$item->name" required />
    <div x-data="slugField(@js(old('slug', $item->slug)), 'name')">
        <label class="a-label" for="slug">Slug</label>
        <input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input">
    </div>
    <x-admin.field name="designation" label="Designation" :value="$item->designation" />
    <x-admin.field type="select" name="user_id" label="Linked admin user" :value="$item->user_id" :options="$users" />
    <x-admin.field type="textarea" name="bio" label="Bio" :value="$item->bio" rows="4" class="md:col-span-2" />
    <x-admin.media-picker name="avatar_id" label="Photo" :media="$item->avatar" />
</div>
@endsection

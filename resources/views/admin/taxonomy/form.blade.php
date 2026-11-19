@extends('admin.content.form', ['tabs' => ['basic' => 'Details']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="name" label="Name" :value="$item->name" required />
    <div x-data="slugField(@js(old('slug', $item->slug)), 'name')">
        <label class="a-label" for="slug">Slug</label>
        <input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input">
        @error('slug')<p class="a-error">{{ $message }}</p>@enderror
    </div>
    @if($withDescription)
        <x-admin.field type="select" name="parent_id" label="Parent" :value="$item->parent_id" :options="$parents" />
        <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
        <x-admin.field type="textarea" name="description" label="Description" :value="$item->description" rows="3" class="md:col-span-2" />
    @else
        <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
    @endif
</div>
@endsection

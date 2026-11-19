@extends('admin.content.form', ['tabs' => ['basic' => 'FAQ']])

@section('tab-basic')
<div class="grid gap-4">
    <x-admin.field name="question" label="Question" :value="$item->question" required />
    <x-admin.field type="textarea" name="answer" label="Answer" :value="$item->answer" editor rows="6" required help="Variables like {{ '{'.'{location_name}'.'}' }} work when the FAQ is shown on programmatic pages." />
    <div class="grid gap-4 md:grid-cols-2">
        <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
        <div class="pt-7"><x-admin.field type="checkbox" name="is_active" label="Active" :value="$item->is_active ?? true" /></div>
    </div>
</div>
@endsection

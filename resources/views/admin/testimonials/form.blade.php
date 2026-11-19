@extends('admin.content.form', ['tabs' => ['basic' => 'Testimonial']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="name" label="Name" :value="$item->name" required />
    <x-admin.field name="designation" label="Designation" :value="$item->designation" />
    <x-admin.field name="company" label="Company" :value="$item->company" />
    <x-admin.field type="select" name="rating" label="Rating" :value="$item->rating ?? 5" :options="[5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 2 => '★★', 1 => '★']" :empty="false" />
    <x-admin.field type="textarea" name="review" label="Review" :value="$item->review" rows="5" required class="md:col-span-2" />
    <x-admin.field type="select" name="service_id" label="Related service" :value="$item->service_id" :options="$services" help="Shown first on that service's pages." />
    <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
    <x-admin.media-picker name="photo_id" label="Photo" :media="$item->photo" />
    <div class="pt-7"><x-admin.field type="checkbox" name="is_active" label="Active" :value="$item->is_active ?? true" /></div>
</div>
@endsection

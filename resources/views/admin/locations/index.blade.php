@extends('admin.content.index')

@section('index-actions')
    @can('locations.create')
        <a href="{{ route('admin.locations.create', ['type' => 'area'] + (request()->integer('city') ? ['parent_id' => request()->integer('city')] : [])) }}" class="a-btn a-btn-light"><x-icon name="map-pin" class="size-4" /> Add area</a>
        <a href="{{ route('admin.locations.create', ['type' => 'city']) }}" class="a-btn a-btn-light"><x-icon name="building" class="size-4" /> Add city</a>
    @endcan
@endsection

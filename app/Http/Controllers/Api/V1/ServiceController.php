<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        return ServiceResource::collection(
            Service::published()->with(['category', 'featuredImage'])->orderBy('sort_order')->paginate(50)
        );
    }

    public function show(string $slug)
    {
        return new ServiceResource(Service::published()->with(['category', 'featuredImage'])->where('slug', $slug)->firstOrFail());
    }
}

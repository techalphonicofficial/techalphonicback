<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use App\Models\Blog;

class BlogController extends Controller
{
    public function index()
    {
        return BlogResource::collection(
            Blog::published()->with(['category', 'author', 'featuredImage'])->latest('published_at')->paginate(20)
        );
    }

    public function show(string $slug)
    {
        return new BlogResource(Blog::published()->with(['category', 'author', 'featuredImage', 'tags'])->where('slug', $slug)->firstOrFail());
    }
}

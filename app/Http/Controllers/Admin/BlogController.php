<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\BlogRequest;
use App\Models\Author;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends ContentController
{
    protected string $modelClass = Blog::class;

    protected string $resource = 'blogs';

    protected string $routeName = 'admin.blogs';

    protected string $viewPath = 'admin.blogs';

    protected string $label = 'Blog post';

    protected string $labelPlural = 'Blog Posts';

    protected array $searchColumns = ['title', 'slug', 'excerpt'];

    protected array $sortable = ['title', 'published_at', 'views'];

    protected array $listWith = ['category:id,name', 'author:id,name'];

    protected array $columns = [
        ['Title', 'title', 'title'],
        ['Category', 'category.name', 'text'],
        ['Author', 'author.name', 'text'],
        ['SEO', 'seo', 'seo'],
        ['Status', 'status', 'status'],
        ['Publish date', 'published_at', 'date'],
    ];

    protected function requestClass(): string
    {
        return BlogRequest::class;
    }

    protected function defaults(): array
    {
        return parent::defaults() + ['show_toc' => true, 'faq_enabled' => true, 'author_id' => Author::where('user_id', auth()->id())->value('id')];
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($cat = $request->integer('category')) {
            $query->where('blog_category_id', $cat);
        }
    }

    protected function filterOptions(): array
    {
        return ['category' => ['label' => 'Category', 'options' => BlogCategory::orderBy('name')->pluck('name', 'id')->all()]];
    }

    protected function formData(Model $item): array
    {
        $item->loadMissing(['tags', 'services']);

        return [
            'categories' => BlogCategory::orderBy('name')->pluck('name', 'id'),
            'authors' => Author::orderBy('name')->pluck('name', 'id'),
            'services' => Service::orderBy('name')->pluck('name', 'id'),
        ];
    }

    protected function afterSave(Model $item, FormRequest $request): void
    {
        $names = collect(explode(',', (string) $request->input('tags')))->map(fn ($t) => trim($t))->filter()->unique()->take(15);
        $ids = $names->map(fn ($name) => BlogTag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id);
        $item->tags()->sync($ids->all());
        $item->services()->sync((array) $request->validated('service_ids', []));
    }
}

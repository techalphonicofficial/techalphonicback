<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\TaxonomyRequest;
use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Model;

class BlogCategoryController extends ContentController
{
    protected string $modelClass = BlogCategory::class;

    protected string $resource = 'blogs';

    protected string $routeName = 'admin.blog-categories';

    protected string $viewPath = 'admin.taxonomy';

    protected string $label = 'Blog category';

    protected string $labelPlural = 'Blog Categories';

    protected array $searchColumns = ['name'];

    protected array $sortable = ['name', 'sort_order'];

    protected array $columns = [
        ['Name', 'name', 'title'],
        ['Slug', 'slug', 'text'],
        ['Posts', 'blogs_count', 'text'],
        ['Order', 'sort_order', 'text'],
    ];

    protected function query()
    {
        return BlogCategory::query()->withCount('blogs');
    }

    protected function requestClass(): string
    {
        return TaxonomyRequest::class;
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function formData(Model $item): array
    {
        return ['withDescription' => true, 'parents' => BlogCategory::whereKeyNot($item->id)->pluck('name', 'id')];
    }
}

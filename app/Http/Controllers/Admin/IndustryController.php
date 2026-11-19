<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\IndustryRequest;
use App\Models\Industry;

class IndustryController extends ContentController
{
    protected string $modelClass = Industry::class;

    protected string $resource = 'industries';

    protected string $routeName = 'admin.industries';

    protected string $viewPath = 'admin.industries';

    protected string $label = 'Industry';

    protected string $labelPlural = 'Industries';

    protected array $searchColumns = ['name', 'slug'];

    protected array $sortable = ['name', 'sort_order'];

    protected array $columns = [
        ['Name', 'name', 'title'],
        ['Plural label', 'plural_name', 'text'],
        ['SEO pages', 'landing_pages_count', 'text'],
        ['Status', 'status', 'badge'],
        ['Order', 'sort_order', 'text'],
    ];

    protected function query()
    {
        return Industry::query()->withCount('landingPages');
    }

    protected function requestClass(): string
    {
        return IndustryRequest::class;
    }

    protected function defaults(): array
    {
        return ['status' => 'published'];
    }
}

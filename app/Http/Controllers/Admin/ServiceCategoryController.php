<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ServiceCategoryRequest;
use App\Models\ServiceCategory;

class ServiceCategoryController extends ContentController
{
    protected string $modelClass = ServiceCategory::class;

    protected string $resource = 'services';

    protected string $routeName = 'admin.service-categories';

    protected string $viewPath = 'admin.service-categories';

    protected string $label = 'Service category';

    protected string $labelPlural = 'Service Categories';

    protected array $searchColumns = ['name'];

    protected array $sortable = ['name', 'sort_order'];

    protected array $columns = [
        ['Name', 'name', 'title'],
        ['Services', 'services_count', 'text'],
        ['Order', 'sort_order', 'text'],
        ['SEO', 'seo', 'seo'],
        ['Status', 'status', 'status'],
    ];

    protected function query()
    {
        return ServiceCategory::query()->withCount('services');
    }

    protected function requestClass(): string
    {
        return ServiceCategoryRequest::class;
    }
}

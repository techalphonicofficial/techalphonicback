<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\LandingPageGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ServiceController extends ContentController
{
    protected string $modelClass = Service::class;

    protected string $resource = 'services';

    protected string $routeName = 'admin.services';

    protected string $viewPath = 'admin.services';

    protected string $label = 'Service';

    protected string $labelPlural = 'Services';

    protected array $searchColumns = ['name', 'slug', 'short_description'];

    protected array $sortable = ['name', 'sort_order'];

    protected array $listWith = ['category:id,name', 'parent:id,name'];

    protected array $columns = [
        ['Name', 'name', 'title'],
        ['Category', 'category.name', 'text'],
        ['Parent', 'parent.name', 'text'],
        ['Featured', 'is_featured', 'bool'],
        ['SEO', 'seo', 'seo'],
        ['Status', 'status', 'status'],
        ['Order', 'sort_order', 'text'],
    ];

    protected function requestClass(): string
    {
        return ServiceRequest::class;
    }

    protected function defaults(): array
    {
        return parent::defaults() + ['hero_layout' => 'split'];
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($cat = $request->integer('category')) {
            $query->where('service_category_id', $cat);
        }
    }

    protected function filterOptions(): array
    {
        return ['category' => ['label' => 'Category', 'options' => ServiceCategory::orderBy('sort_order')->pluck('name', 'id')->all()]];
    }

    protected function formData(Model $item): array
    {
        return [
            'categories' => ServiceCategory::orderBy('sort_order')->pluck('name', 'id'),
            'parents' => Service::whereKeyNot($item->id)->orderBy('name')->pluck('name', 'id'),
            'templateDefaults' => LandingPageGenerator::DEFAULT_TEMPLATES,
        ];
    }
}
